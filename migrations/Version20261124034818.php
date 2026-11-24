<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261124034818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona destaque ao evento (bloco do topo da home)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evento ADD destaque TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evento DROP destaque');
    }
}
