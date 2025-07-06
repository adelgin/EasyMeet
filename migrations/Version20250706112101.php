<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250706112101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meeting ADD COLUMN title VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__meeting AS SELECT id, creator_id, start_at, end_at, status FROM meeting');
        $this->addSql('DROP TABLE meeting');
        $this->addSql('CREATE TABLE meeting (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, creator_id INTEGER NOT NULL, start_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , end_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , status VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_F515E13961220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO meeting (id, creator_id, start_at, end_at, status) SELECT id, creator_id, start_at, end_at, status FROM __temp__meeting');
        $this->addSql('DROP TABLE __temp__meeting');
        $this->addSql('CREATE INDEX IDX_F515E13961220EA6 ON meeting (creator_id)');
    }
}
