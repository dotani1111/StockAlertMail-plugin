<?php

declare(strict_types=1);

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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320000000 extends AbstractMigration
{
    public const NAME = 'plg_stock_alert_config';

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::NAME)) {
            return;
        }

        $table = $schema->createTable(self::NAME);
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('threshold', 'integer', ['default' => 5]);
        $table->addColumn('alert_emails', 'string', ['length' => 1000, 'notnull' => false]);
        $table->addColumn('mail_subject', 'string', ['length' => 500, 'notnull' => false]);
        $table->addColumn('mail_body', 'text', ['notnull' => false]);
        $table->addColumn('create_date', 'datetimetz');
        $table->addColumn('update_date', 'datetimetz');
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::NAME)) {
            return;
        }

        $schema->dropTable(self::NAME);
    }
}
