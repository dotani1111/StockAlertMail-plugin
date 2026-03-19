<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StockAlertMail\Tests;

use Eccube\Entity\BaseInfo;
use Eccube\Tests\EccubeTestCase;
use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Plugin\StockAlertMail\Entity\StockAlertLog;
use Plugin\StockAlertMail\Repository\StockAlertConfigRepository;
use Plugin\StockAlertMail\Repository\StockAlertLogRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mime\Email;

class StockAlertCommandTest extends EccubeTestCase
{
    use MailerAssertionsTrait;

    /** @var StockAlertConfigRepository */
    private $configRepository;

    /** @var StockAlertLogRepository */
    private $logRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configRepository = $this->entityManager->getRepository(StockAlertConfig::class);
        $this->logRepository = $this->entityManager->getRepository(StockAlertLog::class);

        // 初期設定を作成（threshold=9999 で全商品をアラート対象にする）
        $config = new StockAlertConfig();
        $config->setThreshold(9999);
        $config->setCreateDate(new \DateTime());
        $config->setUpdateDate(new \DateTime());
        $this->entityManager->persist($config);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // bootKernel()後にエンティティがdetachedになる場合があるためDQLで削除
        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertLog l')->execute();
        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertConfig c')->execute();

        parent::tearDown();
    }

    public function testCommandSuccess()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('eccube:plugin:stock-alert-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testNoDuplicateSend()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('eccube:plugin:stock-alert-mail');
        $commandTester = new CommandTester($command);

        // 1回目実行
        $commandTester->execute([]);
        $logCount = count($this->logRepository->findAll());

        // 2回目実行してもログが増えないことを確認
        $commandTester->execute([]);
        $this->assertSame($logCount, count($this->logRepository->findAll()));
    }

    public function testMailSentWhenLowStock()
    {
        // 在庫100〜999の商品を作成（threshold=9999 なのでアラート対象になる）
        $this->createProduct();

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('eccube:plugin:stock-alert-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertEmailCount(1);

        /** @var Email $message */
        $message = $this->getMailerMessage(0);
        $this->assertStringContainsString('在庫アラート通知', $message->getSubject());

        /** @var BaseInfo $baseInfo */
        $baseInfo = $this->entityManager->find(BaseInfo::class, 1);
        $this->assertEmailAddressContains($message, 'to', $baseInfo->getEmail01());
    }

    public function testNoMailWhenNoLowStock()
    {
        // threshold=0 に変更（商品の在庫は100以上なので対象外）
        $config = $this->configRepository->findOneBy([]);
        $config->setThreshold(0);
        $this->entityManager->flush();

        $this->createProduct();

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('eccube:plugin:stock-alert-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertEmailCount(0);
    }

    public function testStockRecoveryResetsLog()
    {
        // 在庫がある商品を作成してアラートログを記録
        $product = $this->createProduct();
        $productClass = $product->getProductClasses()->filter(
            fn ($pc) => !$pc->isStockUnlimited() && $pc->isVisible()
        )->first();

        if (!$productClass) {
            $this->markTestSkipped('対象のProductClassが存在しません。');
        }

        // ログを手動で記録（送信済みとする）
        $log = new StockAlertLog();
        $log->setProductClass($productClass);
        $log->setAlertedAt(new \DateTime());
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        // threshold=0 に変更（商品の在庫は回復済み扱い）
        $config = $this->configRepository->findOneBy([]);
        $config->setThreshold(0);
        $this->entityManager->flush();

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('eccube:plugin:stock-alert-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        // 在庫回復によりログが削除されていること
        $this->assertNull($this->logRepository->findOneBy(['ProductClass' => $productClass]));
    }
}
