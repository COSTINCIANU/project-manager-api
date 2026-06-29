<?php

// =====================================================
// MoteurRegle.php — Moteur d'exécution des règles
// Vérifie et exécute les règles automatiquement
// quand une tâche est créée ou modifiée
// Appelé depuis TaskController
// =====================================================

namespace App\Service;

use App\Entity\RegleAutomatisation;
use App\Entity\Task;
use App\Repository\RegleAutomatisationRepository;
use Doctrine\ORM\EntityManagerInterface;

class MoteurRegle
{
    public function __construct(
        private RegleAutomatisationRepository $regleRepository,
        private EntityManagerInterface $em,
    ) {
    }

    // =====================
    // VÉRIFIER LES RÈGLES APRÈS CHANGEMENT DE STATUT
    // Appelé depuis TaskController quand on modifie le statut d'une tâche
    // $ancienStatut et $nouveauStatut : ex. "En cours", "Terminé"
    // =====================
    public function verifierChangementStatut(Task $tache, string $ancienStatut, string $nouveauStatut): void
    {
        // Pas de vérification si le statut n'a pas changé
        if ($ancienStatut === $nouveauStatut) {
            return;
        }

        // Récupère toutes les règles actives du projet sur ce déclencheur
        $regles = $this->regleRepository->trouverParDeclencheur(
            $tache->getProject()?->getId() ?? 0,
            'tache_statut_change'
        );

        foreach ($regles as $regle) {
            // Déclenche si la valeur correspond au nouveau statut ou si pas de valeur définie
            if ($regle->getValeurDeclencheur() === $nouveauStatut || null === $regle->getValeurDeclencheur()) {
                $this->executerAction($regle, $tache);
            }
        }
    }

    // =====================
    // VÉRIFIER LES RÈGLES À LA CRÉATION D'UNE TÂCHE
    // Appelé depuis TaskController quand on crée une nouvelle tâche
    // =====================
    public function verifierCreationTache(Task $tache): void
    {
        $regles = $this->regleRepository->trouverParDeclencheur(
            $tache->getProject()?->getId() ?? 0,
            'tache_creee'
        );

        foreach ($regles as $regle) {
            $this->executerAction($regle, $tache);
        }
    }

    // =====================
    // EXÉCUTER L'ACTION D'UNE RÈGLE
    // Méthode privée — appelée uniquement en interne
    // =====================
    private function executerAction(RegleAutomatisation $regle, Task $tache): void
    {
        switch ($regle->getAction()) {
            // Change la priorité de la tâche
            case 'changer_priorite':
                if ($regle->getValeurAction()) {
                    $tache->setPriority($regle->getValeurAction());
                    $this->em->flush();
                }

                break;

                // Notifie le manager — journalise pour l'instant, email à brancher plus tard
            case 'notifier_manager':
                error_log(sprintf(
                    '[MoteurRegle] Règle "%s" déclenchée sur tâche #%d — notification manager',
                    $regle->getNom(),
                    $tache->getId()
                ));

                break;

                // Envoie un email — à brancher sur Symfony Mailer
            case 'envoyer_email':
                error_log(sprintf(
                    '[MoteurRegle] Règle "%s" — email vers %s pour tâche #%d',
                    $regle->getNom(),
                    $regle->getValeurAction() ?? 'non défini',
                    $tache->getId()
                ));

                break;
        }
    }
}
