<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221007132553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE customer_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE token_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE customer (id BIGINT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, dni BIGINT NOT NULL, name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone BIGINT NOT NULL, balance DOUBLE PRECISION DEFAULT NULL, token_email VARCHAR(255) DEFAULT NULL, session_id VARCHAR(255) DEFAULT NULL, creation_date VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE token (id BIGINT NOT NULL, customer_id BIGINT NOT NULL, creation_date VARCHAR(255) NOT NULL, expiration_date VARCHAR(255) NOT NULL, token VARCHAR(500) NOT NULL, activate VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5F37A13B9395C3F3 ON token (customer_id)');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13B9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE customer_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE token_id_seq CASCADE');
        $this->addSql('ALTER TABLE token DROP CONSTRAINT FK_5F37A13B9395C3F3');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE token');
    }
}
