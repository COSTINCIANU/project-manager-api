<?php
// =====================================================
// ActionLogService.php — Service d'historique des actions
// Enregistre chaque action importante en base de données
// =====================================================

namespace App\Service;

use App\Entity\ActionLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActionLogService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    // Enregistre une action dans l'historique
    public function log(
        string $action,
        string $description,
        string $entityType = null,
        int $entityId = null
    ): void {
        // Récupère l'email de l'utilisateur connecté
        $user = $this->security->getUser();
        $userEmail = $user ? $user->getUserIdentifier() : 'système';

        $log = new ActionLog();
        $log->setAction($action);
        $log->setDescription($description);
        $log->setUserEmail($userEmail);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);

        $this->em->persist($log);
        $this->em->flush();
    }
}
