<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_1_0_1 extends AbstractMigration
{
    private string $table = 'hello_world';

    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix($this->table))->hasColumn('is_enabled');
        } catch (SchemaException $e) {
            return false;
        }
    }

    protected function up(): void
    {
        $this->addSql("ALTER TABLE `{$this->concatPrefix($this->table)}` ADD `is_enabled` tinyint(1) 0");

        $this->addSql("CREATE INDEX {$this->concatPrefix('is_enabled')} ON {$this->concatPrefix($this->table)}(is_enabled);");
    }
}
