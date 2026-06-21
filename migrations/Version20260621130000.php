<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table custom_field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE custom_field (
            id INT AUTO_INCREMENT NOT NULL,
            project_id INT NOT NULL,
            task_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            label VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            value LONGTEXT DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX IDX_project (project_id),
            INDEX IDX_task (task_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE custom_field');
    }
}
