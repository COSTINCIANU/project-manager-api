<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout download_token et download_token_expiry dans la table user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD download_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD download_token_expiry DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN download_token');
        $this->addSql('ALTER TABLE user DROP COLUMN download_token_expiry');
    }
}
