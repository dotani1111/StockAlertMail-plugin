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

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configRepository = $this->entityManager->getRepository(StockAlertConfig::class);
        $this->logRepository = $this->entityManager->getRepository(StockAlertLog::class);

        // eccube:plugin:install で作成された初期設定も含め全データをクリーンアップ
        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertLog l')->execute();
        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertConfig c')->execute();
        // DQL DELETE はアイデンティティマップを更新しないため、手動でクリアして古い参照を除去する
        $this->entityManager->clear();

        // setUp済みのカーネルをそのまま使い、再ブートによるEntityManager無効化を防ぐ
        $application = new Application(static::$kernel);
        $command = $application->find('eccube:plugin:stock-alert-mail');
        $this->commandTester = new CommandTester($command);

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
        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertLog l')->execute();
        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertConfig c')->execute();

        parent::tearDown();
    }

    public function testCommandSuccessNoAlert()
    {
        // 閾値を-1にして、どの商品もアラート対象にならないようにする
        $config = $this->configRepository->find(1);
        $config->setThreshold(-1);
        $this->entityManager->flush();

        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode(), $this->commandTester->getDisplay());
    }

    public function testNoDuplicateSend()
    {
        // 1回目実行
        $this->commandTester->execute([]);
        $logCount = count($this->logRepository->findAll());

        // 2回目実行してもログが増えないことを確認
        $this->commandTester->execute([]);
        $this->assertSame($logCount, count($this->logRepository->findAll()));
    }

    public function testMailSentWhenLowStock()
    {
        // 在庫100〜999の商品を作成（threshold=9999 なのでアラート対象になる）
        $this->createProduct();

        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode(), $this->commandTester->getDisplay());
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
        // threshold=-1 に変更（stock は 0 以上なので対象外）
        $config = $this->configRepository->find(1);
        $config->setThreshold(-1);
        $this->entityManager->flush();

        $this->createProduct();

        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertEmailCount(0);
    }

    public function testStockRecoveryResetsLog()
    {
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

        // threshold=-1 に変更（在庫回復済み扱い）
        $config = $this->configRepository->find(1);
        $config->setThreshold(-1);
        $this->entityManager->flush();

        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        // 在庫回復によりログが削除されていること
        $this->assertNull($this->logRepository->findOneBy(['ProductClass' => $productClass]));
    }

    public function testMailSubjectFormat()
    {
        $this->createProduct();

        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode(), $this->commandTester->getDisplay());
        $this->assertEmailCount(1);

        /** @var Email $message */
        $message = $this->getMailerMessage(0);
        // 件名が "[ショップ名] 在庫アラート通知" の形式であること
        $this->assertStringContainsString('在庫アラート通知', $message->getSubject());
        $this->assertStringStartsWith('[', $message->getSubject());
    }

    public function testMailBodyFromDefaultTemplate()
    {
        $this->createProduct();

        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode(), $this->commandTester->getDisplay());
        $this->assertEmailCount(1);

        /** @var Email $message */
        $message = $this->getMailerMessage(0);
        // デフォルトTwigテンプレートの文字列が含まれること
        $body = $message->getTextBody();
        $this->assertStringContainsString('管理者様', $body);
        $this->assertStringContainsString('在庫', $body);
    }
}
