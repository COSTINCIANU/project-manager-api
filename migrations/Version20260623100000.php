<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration — Création de la table regle_automatisation
 * Stocke les règles "Quand X → faire Y" liées à un projet
 * Date : 2026-06-23
 */
final class Version20260623100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table regle_automatisation';
    }

    // =====================
    // MIGRATION VERS LE HAUT — création de la table
    // =====================
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE regle_automatisation (
                id                  INT AUTO_INCREMENT NOT NULL,
                projet_id           INT NOT NULL,
                nom                 VARCHAR(255) NOT NULL COMMENT "Nom lisible de la règle affiché dans l\'interface",
                declencheur         VARCHAR(100) NOT NULL COMMENT "Ce qui provoque la règle : tache_statut_change, tache_creee, tache_assignee, tache_en_retard",
                valeur_declencheur  VARCHAR(100) DEFAULT NULL COMMENT "Valeur associée au déclencheur, ex: Terminé",
                action              VARCHAR(100) NOT NULL COMMENT "Ce qui se passe : notifier_manager, changer_priorite, envoyer_email",
                valeur_action       VARCHAR(255) DEFAULT NULL COMMENT "Valeur associée à l\'action, ex: haute ou email@example.com",
                active              TINYINT(1) NOT NULL DEFAULT 1 COMMENT "1 = règle active, 0 = désactivée",
                cree_le             DATETIME NOT NULL COMMENT "Date de création de la règle",
                INDEX IDX_PROJET (projet_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        // Clé étrangère vers la table project
        // Si le projet est supprimé, toutes ses règles sont supprimées automatiquement
        $this->addSql('
            ALTER TABLE regle_automatisation
            ADD CONSTRAINT FK_REGLE_PROJET
            FOREIGN KEY (projet_id)
            REFERENCES project (id)
            ON DELETE CASCADE
        ');
    }

    // =====================
    // MIGRATION VERS LE BAS — suppression de la table
    // Utilisé si on veut annuler cette migration
    // =====================
    public function down(Schema $schema): void
    {
        // Supprime d'abord la contrainte de clé étrangère
        $this->addSql('ALTER TABLE regle_automatisation DROP FOREIGN KEY FK_REGLE_PROJET');

        // Puis supprime la table
        $this->addSql('DROP TABLE regle_automatisation');
    }
}
