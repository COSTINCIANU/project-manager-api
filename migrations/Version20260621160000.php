<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table sprint_history pour le Burndown chart';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sprint_history (
            id INT AUTO_INCREMENT NOT NULL,
            sprint_id INT NOT NULL,
            date DATE NOT NULL,
            tasks_total INT NOT NULL,
            tasks_remaining INT NOT NULL,
            tasks_done INT NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_sprint_history_sprint (sprint_id),
            UNIQUE INDEX IDX_sprint_date (sprint_id, date)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sprint_history');
    }
}
