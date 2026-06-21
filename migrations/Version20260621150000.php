<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table sprint + ajout sprintId dans task';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sprint (
            id INT AUTO_INCREMENT NOT NULL,
            project_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            goal VARCHAR(500) DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            start_date VARCHAR(20) DEFAULT NULL,
            end_date VARCHAR(20) DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX IDX_sprint_project (project_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE task ADD sprint_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP COLUMN sprint_id');
        $this->addSql('DROP TABLE sprint');
    }
}
