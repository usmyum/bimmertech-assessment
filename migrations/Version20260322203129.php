<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260322203129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create software_version table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('software_versions');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 150]);
        $table->addColumn('system_version', 'string', ['length' => 100]);
        $table->addColumn('system_version_alt', 'string', ['length' => 100]);
        $table->addColumn('link', 'text');
        $table->addColumn('st', 'text', ['notnull' => false]);
        $table->addColumn('gd', 'text', ['notnull' => false]);
        $table->addColumn('latest', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);

        $table->addIndex(['system_version_alt'], 'idx_version_alt');
    }

    public function down(Schema $schema): void
    {
        // The "helper" way to drop a table
        $schema->dropTable('software_version');
    }
}
