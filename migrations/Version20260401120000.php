<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cartes_virtuelles table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cartes_virtuelles (
            id INT AUTO_INCREMENT NOT NULL,
            compte_id INT NOT NULL,
            type VARCHAR(16) NOT NULL,
            numero VARCHAR(19) NOT NULL,
            cvv VARCHAR(3) NOT NULL,
            date_expiration DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            statut VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CARTE_COMPTE (compte_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE cartes_virtuelles ADD CONSTRAINT FK_CARTE_COMPTE_ID
            FOREIGN KEY (compte_id) REFERENCES comptes_bancaires (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cartes_virtuelles DROP FOREIGN KEY FK_CARTE_COMPTE_ID');
        $this->addSql('DROP TABLE cartes_virtuelles');
    }
}
