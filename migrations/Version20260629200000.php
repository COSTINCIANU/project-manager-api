<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout champs recurrence et recurrenceEndDate dans Task';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD recurrence VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD recurrence_end_date VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP COLUMN recurrence');
        $this->addSql('ALTER TABLE task DROP COLUMN recurrence_end_date');
    }
}
