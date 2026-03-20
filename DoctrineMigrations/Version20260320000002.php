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

final class Version20260320000002 extends AbstractMigration
{
    public const NAME = 'plg_stock_alert_config';

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::NAME)) {
            return;
        }

        $table = $schema->getTable(self::NAME);

        if (!$table->hasColumn('mail_subject')) {
            $table->addColumn('mail_subject', 'string', ['length' => 500, 'notnull' => false]);
        }

        if (!$table->hasColumn('mail_body')) {
            $table->addColumn('mail_body', 'text', ['notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::NAME)) {
            return;
        }

        $table = $schema->getTable(self::NAME);

        if ($table->hasColumn('mail_body')) {
            $table->dropColumn('mail_body');
        }

        if ($table->hasColumn('mail_subject')) {
            $table->dropColumn('mail_subject');
        }
    }
}
