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

/**
 * メールテンプレートを dtb_mail_template に移行するため、
 * plg_stock_alert_config から mail_subject・mail_body カラムを削除する。
 */
final class Version20260321000000 extends AbstractMigration
{
    public const TABLE = 'plg_stock_alert_config';

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $table = $schema->getTable(self::TABLE);

        if ($table->hasColumn('mail_subject')) {
            $table->dropColumn('mail_subject');
        }

        if ($table->hasColumn('mail_body')) {
            $table->dropColumn('mail_body');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $table = $schema->getTable(self::TABLE);

        if (!$table->hasColumn('mail_subject')) {
            $table->addColumn('mail_subject', 'string', ['length' => 500, 'notnull' => false]);
        }

        if (!$table->hasColumn('mail_body')) {
            $table->addColumn('mail_body', 'text', ['notnull' => false]);
        }
    }
}
