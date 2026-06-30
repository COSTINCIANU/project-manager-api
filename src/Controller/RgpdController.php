<?php
// =====================================================
// RgpdController.php — Conformité RGPD
// Permet à l'utilisateur de :
// - Exporter toutes ses données personnelles (JSON)
// - Supprimer définitivement son compte
// =====================================================

namespace App\Controller;

use App\Entity\ActionLog;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user')]
class RgpdController extends AbstractController
{
    // =====================
    // GET — Exporter toutes les données personnelles
    // Conforme RGPD article 20 (droit à la portabilité)
    // =====================
    #[Route('/export', methods: ['GET'])]
    public function exporterDonnees(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Récupère toutes les actions liées à cet utilisateur
        $logs = $em->getRepository(ActionLog::class)->findBy(['userEmail' => $user->getEmail()]);

        // Récupère toutes les tâches assignées à cet utilisateur
        $taches = $em->getRepository(Task::class)->findBy(['assignedTo' => $user->getId()]);

        $donnees = [
            'profil' => [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'name'      => $user->getName(),
                'role'      => $user->getRole(),
                'plan'      => $user->getPlan(),
                'avatar'    => $user->getAvatar(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            ],
            'tachesAssignees' => array_map(fn (Task $t) => [
                'id'       => $t->getId(),
                'name'     => $t->getName(),
                'priority' => $t->getPriority(),
                'done'     => $t->isDone(),
                'dueDate'  => $t->getDueDate(),
            ], $taches),
            'historiqueActions' => array_map(fn (ActionLog $l) => [
                'action'      => $l->getAction(),
                'description' => $l->getDescription(),
                'createdAt'   => $l->getCreatedAt()->format('Y-m-d H:i:s'),
                'ipAddress'   => $l->getIpAddress(),
            ], $logs),
            'dateExport' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $json = json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Retourne le fichier JSON en téléchargement
        return new Response($json, 200, [
            'Content-Type'        => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mes-donnees-'.date('Y-m-d').'.json"',
        ]);
    }

    // =====================
    // DELETE — Supprimer définitivement le compte
    // Conforme RGPD article 17 (droit à l'effacement)
    // Anonymise les actions passées plutôt que de les supprimer
    // pour préserver l'intégrité de l'historique du projet
    // =====================
    #[Route('/me', methods: ['DELETE'])]
    public function supprimerCompte(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $userId = $user->getId();

        // Anonymise les actions passées de l'utilisateur — conserve l'historique du projet
        // sans garder les données personnelles identifiables
        $logs = $em->getRepository(ActionLog::class)->findBy(['userEmail' => $user->getEmail()]);
        foreach ($logs as $log) {
            $log->setUserEmail('utilisateur-supprime');
            $log->setIpAddress(null);
        }

        // Désassigne les tâches de l'utilisateur — elles restent dans le projet
        $taches = $em->getRepository(Task::class)->findBy(['assignedTo' => $userId]);
        foreach ($taches as $tache) {
            $tache->setAssignedTo(null);
        }

        $em->flush();

        // Supprime définitivement le compte utilisateur
        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Compte supprimé définitivement.']);
    }
}
