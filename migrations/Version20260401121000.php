<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add carte_virtuelle_id FK to transactions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions ADD carte_virtuelle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_TX_CARTE
            FOREIGN KEY (carte_virtuelle_id) REFERENCES cartes_virtuelles (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_TX_CARTE ON transactions (carte_virtuelle_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_TX_CARTE');
        $this->addSql('DROP INDEX IDX_TX_CARTE ON transactions');
        $this->addSql('ALTER TABLE transactions DROP COLUMN carte_virtuelle_id');
    }
}
