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

use Doctrine\DBAL\Schema\Schema;
use Eccube\Tests\EccubeTestCase;
use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Plugin\StockAlertMail\PluginManager;
use Plugin\StockAlertMail\Repository\StockAlertConfigRepository;

class PluginManagerTest extends EccubeTestCase
{
    /** @var StockAlertConfigRepository */
    private $configRepository;

    /** @var PluginManager */
    private $pluginManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configRepository = $this->entityManager->getRepository(StockAlertConfig::class);
        $this->pluginManager = new PluginManager();

        // 既存設定をクリーンアップ
        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertConfig c')->execute();
    }

    protected function tearDown(): void
    {
        // uninstallテストでテーブルが削除されている場合は再作成する
        $this->recreateTablesIfNeeded();

        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertConfig c')->execute();

        parent::tearDown();
    }

    public function testEnableCreatesInitialConfig()
    {
        $this->pluginManager->enable([], static::getContainer());

        $config = $this->configRepository->findOneBy([]);
        $this->assertNotNull($config, '設定が作成されていること');
        $this->assertSame(5, $config->getThreshold(), '初期閾値が5であること');
    }

    public function testEnableSkipsIfConfigExists()
    {
        // 先に設定を作成しておく
        $existing = new StockAlertConfig();
        $existing->setThreshold(99);
        $existing->setCreateDate(new \DateTime());
        $existing->setUpdateDate(new \DateTime());
        $this->entityManager->persist($existing);
        $this->entityManager->flush();

        $this->pluginManager->enable([], static::getContainer());

        // 設定が1件のみで既存の値が変わっていないこと
        $configs = $this->configRepository->findAll();
        $this->assertCount(1, $configs);
        $this->assertSame(99, $configs[0]->getThreshold());
    }

    public function testUninstallDropsTables()
    {
        $conn = $this->entityManager->getConnection();

        $this->assertTrue(
            $conn->createSchemaManager()->tablesExist(['plg_stock_alert_config']),
            'アンインストール前にplg_stock_alert_configテーブルが存在すること'
        );
        $this->assertTrue(
            $conn->createSchemaManager()->tablesExist(['plg_stock_alert_log']),
            'アンインストール前にplg_stock_alert_logテーブルが存在すること'
        );

        $this->pluginManager->uninstall([], static::getContainer());

        $this->assertFalse(
            $conn->createSchemaManager()->tablesExist(['plg_stock_alert_config']),
            'アンインストール後にplg_stock_alert_configテーブルが削除されていること'
        );
        $this->assertFalse(
            $conn->createSchemaManager()->tablesExist(['plg_stock_alert_log']),
            'アンインストール後にplg_stock_alert_logテーブルが削除されていること'
        );
    }

    /**
     * uninstallテストでテーブルが削除されている場合に再作成する。
     * 他のテストへの影響を防ぐために tearDown で呼び出す。
     */
    private function recreateTablesIfNeeded(): void
    {
        $conn = $this->entityManager->getConnection();
        $schemaManager = $conn->createSchemaManager();
        $platform = $conn->getDatabasePlatform();

        if (!$schemaManager->tablesExist(['plg_stock_alert_config'])) {
            $schema = new Schema();
            $table = $schema->createTable('plg_stock_alert_config');
            $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $table->addColumn('threshold', 'integer', ['default' => 5]);
            $table->addColumn('alert_emails', 'string', ['length' => 1000, 'notnull' => false]);
            $table->addColumn('create_date', 'datetimetz');
            $table->addColumn('update_date', 'datetimetz');
            $table->setPrimaryKey(['id']);
            foreach ($schema->toSql($platform) as $sql) {
                $conn->executeStatement($sql);
            }
        }

        if (!$schemaManager->tablesExist(['plg_stock_alert_log'])) {
            $schema = new Schema();
            $table = $schema->createTable('plg_stock_alert_log');
            $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $table->addColumn('product_class_id', 'integer', ['unsigned' => true]);
            $table->addColumn('alerted_at', 'datetimetz');
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['product_class_id']);
            foreach ($schema->toSql($platform) as $sql) {
                $conn->executeStatement($sql);
            }
        }
    }
}
