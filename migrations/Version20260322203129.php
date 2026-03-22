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
        $table = $schema->createTable('software_version');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 100]);
        $table->addColumn('system_version', 'string', ['length' => 100]);
        $table->addColumn('system_version_alt', 'string', ['length' => 100]);
        $table->addColumn('link', 'string', ['length' => 500, 'notnull' => false]);
        $table->addColumn('st', 'string', ['length' => 500, 'notnull' => false]);
        $table->addColumn('gd', 'string', ['length' => 500, 'notnull' => false]);
        $table->addColumn('latest', 'boolean', ['default' => false]);
    }

    public function down(Schema $schema): void
    {
        // The "helper" way to drop a table
        $schema->dropTable('software_version');
    }
}
