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

        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertConfig c')->execute();
    }

    protected function tearDown(): void
    {
        // uninstallテストでテーブルが削除されている場合は再作成する
        $this->recreateTablesIfNeeded();

        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertConfig c')->execute();

        parent::tearDown();
    }

    /**
     * enable: 初期設定（threshold=5）が作成されること。
     */
    public function testEnable()
    {
        $this->pluginManager->enable([], static::getContainer());

        $config = $this->configRepository->findOneBy([]);
        $this->assertNotNull($config, '設定が作成されていること');
        $this->assertSame(5, $config->getThreshold(), '初期閾値が5であること');
    }

    /**
     * enable: 設定が既にある場合は上書きしないこと。
     */
    public function testEnableSkipsIfConfigExists()
    {
        $existing = new StockAlertConfig();
        $existing->setThreshold(99);
        $existing->setCreateDate(new \DateTime());
        $existing->setUpdateDate(new \DateTime());
        $this->entityManager->persist($existing);
        $this->entityManager->flush();

        $this->pluginManager->enable([], static::getContainer());

        $configs = $this->configRepository->findAll();
        $this->assertCount(1, $configs);
        $this->assertSame(99, $configs[0]->getThreshold());
    }

    /**
     * uninstall: プラグインのテーブルが削除されること。
     */
    public function testUninstall()
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
     * install: エラーが発生せず、テーブルが存在すること。
     */
    public function testInstall()
    {
        $conn = $this->entityManager->getConnection();

        // install() はテーブル作成の独自ロジックを持たないが、エラーが発生しないことを確認する
        $this->pluginManager->install([], static::getContainer());

        $this->assertTrue(
            $conn->createSchemaManager()->tablesExist(['plg_stock_alert_config']),
            'install後にplg_stock_alert_configテーブルが存在すること'
        );
        $this->assertTrue(
            $conn->createSchemaManager()->tablesExist(['plg_stock_alert_log']),
            'install後にplg_stock_alert_logテーブルが存在すること'
        );
    }

    /**
     * disable: 設定が保持されること（再enableで使い回せること）。
     */
    public function testDisable()
    {
        $existing = new StockAlertConfig();
        $existing->setThreshold(42);
        $existing->setCreateDate(new \DateTime());
        $existing->setUpdateDate(new \DateTime());
        $this->entityManager->persist($existing);
        $this->entityManager->flush();

        // disable() はデータ削除の独自ロジックを持たないが、エラーが発生せず設定が残ることを確認する
        $this->pluginManager->disable([], static::getContainer());

        $config = $this->configRepository->findOneBy([]);
        $this->assertNotNull($config, 'disable後も設定が保持されること');
        $this->assertSame(42, $config->getThreshold(), '設定値が変更されていないこと');
    }

    /**
     * uninstall → enable: アンインストール後に再インストール（enable）できること。
     */
    public function testUninstallThenReinstall()
    {
        $this->pluginManager->uninstall([], static::getContainer());

        $conn = $this->entityManager->getConnection();
        $this->assertFalse(
            $conn->createSchemaManager()->tablesExist(['plg_stock_alert_config']),
            'アンインストール後にテーブルが削除されていること'
        );

        // テーブルを再作成（eccube:plugin:install 相当）
        $this->recreateTablesIfNeeded();

        // EntityManagerのキャッシュをクリア
        $this->entityManager->clear();

        // 再enableできること
        $this->pluginManager->enable([], static::getContainer());

        $config = $this->configRepository->findOneBy([]);
        $this->assertNotNull($config, '再enable後に設定が作成されること');
        $this->assertSame(5, $config->getThreshold(), '初期閾値が5であること');
    }

    /**
     * uninstallテストでテーブルが削除されている場合に再作成する。
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
