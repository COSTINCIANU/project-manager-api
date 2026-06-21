<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ ticket_type dans la table task';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD ticket_type VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP COLUMN ticket_type');
    }
}
