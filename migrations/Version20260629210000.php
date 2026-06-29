<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création table jalon';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE jalon (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            date VARCHAR(20) NOT NULL,
            projet_id INT NOT NULL,
            atteint TINYINT(1) NOT NULL DEFAULT 0,
            couleur VARCHAR(20) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE jalon');
    }
}
