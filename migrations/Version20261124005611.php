<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261124005611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria categorias, liga cada evento a uma categoria e troca publicado por ativo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categoria (id INT AUTO_INCREMENT NOT NULL, nome VARCHAR(80) NOT NULL, cor VARCHAR(7) NOT NULL, ativa TINYINT NOT NULL, UNIQUE INDEX UNIQ_4E10122D54BD530C (nome), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        // Renomeia publicado -> ativo preservando os valores.
        $this->addSql('ALTER TABLE evento CHANGE publicado ativo TINYINT NOT NULL');

        // Eventos já cadastrados vão para a categoria "Geral"; depois a coluna passa a ser obrigatória.
        $this->addSql("INSERT INTO categoria (nome, cor, ativa) VALUES ('Geral', '#6366f1', 1)");
        $this->addSql('ALTER TABLE evento ADD categoria_id INT DEFAULT NULL');
        $this->addSql("UPDATE evento SET categoria_id = (SELECT id FROM categoria WHERE nome = 'Geral')");
        $this->addSql('ALTER TABLE evento MODIFY categoria_id INT NOT NULL');

        $this->addSql('ALTER TABLE evento ADD CONSTRAINT FK_47860B053397707A FOREIGN KEY (categoria_id) REFERENCES categoria (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_47860B053397707A ON evento (categoria_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evento DROP FOREIGN KEY FK_47860B053397707A');
        $this->addSql('DROP INDEX IDX_47860B053397707A ON evento');
        $this->addSql('ALTER TABLE evento DROP categoria_id, CHANGE ativo publicado TINYINT NOT NULL');
        $this->addSql('DROP TABLE categoria');
    }
}
