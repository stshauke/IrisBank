<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311142617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comptes_bancaires (id INT AUTO_INCREMENT NOT NULL, iban VARCHAR(34) NOT NULL, type VARCHAR(20) NOT NULL, solde NUMERIC(15, 2) NOT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_B0B5D178FAD56E62 (iban), INDEX IDX_B0B5D178A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transactions (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, montant NUMERIC(15, 2) NOT NULL, libelle VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, compte_source_id INT DEFAULT NULL, compte_destinataire_id INT DEFAULT NULL, INDEX IDX_EAA81A4C56B22253 (compte_source_id), INDEX IDX_EAA81A4C6D34063E (compte_destinataire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE comptes_bancaires ADD CONSTRAINT FK_B0B5D178A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C56B22253 FOREIGN KEY (compte_source_id) REFERENCES comptes_bancaires (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C6D34063E FOREIGN KEY (compte_destinataire_id) REFERENCES comptes_bancaires (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comptes_bancaires DROP FOREIGN KEY FK_B0B5D178A76ED395');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C56B22253');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C6D34063E');
        $this->addSql('DROP TABLE comptes_bancaires');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
