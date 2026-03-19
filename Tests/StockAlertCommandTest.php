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

use Eccube\Tests\EccubeTestCase;
use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Plugin\StockAlertMail\Entity\StockAlertLog;
use Plugin\StockAlertMail\Repository\StockAlertConfigRepository;
use Plugin\StockAlertMail\Repository\StockAlertLogRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class StockAlertCommandTest extends EccubeTestCase
{
    /** @var StockAlertConfigRepository */
    private $configRepository;

    /** @var StockAlertLogRepository */
    private $logRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configRepository = $this->entityManager->getRepository(StockAlertConfig::class);
        $this->logRepository = $this->entityManager->getRepository(StockAlertLog::class);

        // 初期設定を作成
        $config = new StockAlertConfig();
        $config->setThreshold(9999);
        $config->setCreateDate(new \DateTime());
        $config->setUpdateDate(new \DateTime());
        $this->entityManager->persist($config);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // ログを全削除
        foreach ($this->logRepository->findAll() as $log) {
            $this->entityManager->remove($log);
        }
        // 設定を全削除
        foreach ($this->configRepository->findAll() as $config) {
            $this->entityManager->remove($config);
        }
        $this->entityManager->flush();

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
}
