<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables project_template et project_template_task';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_template (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description VARCHAR(500) DEFAULT NULL,
            color VARCHAR(7) DEFAULT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('CREATE TABLE project_template_task (
            id INT AUTO_INCREMENT NOT NULL,
            template_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            priority VARCHAR(50) DEFAULT NULL,
            position INT NOT NULL,
            INDEX IDX_template (template_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_template FOREIGN KEY (template_id) REFERENCES project_template (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project_template_task');
        $this->addSql('DROP TABLE project_template');
    }
}
