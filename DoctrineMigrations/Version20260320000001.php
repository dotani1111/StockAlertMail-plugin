<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320000001 extends AbstractMigration
{
    public const NAME = 'plg_stock_alert_log';

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::NAME)) {
            return;
        }

        $table = $schema->createTable(self::NAME);
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('product_class_id', 'integer', ['unsigned' => true]);
        $table->addColumn('alerted_at', 'datetimetz');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_class_id']);
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::NAME)) {
            return;
        }

        $schema->dropTable(self::NAME);
    }
}
