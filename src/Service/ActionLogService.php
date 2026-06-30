<?php
// =====================================================
// ActionLogService.php — Service d'historique des actions
// Enregistre chaque action et publie en temps réel via Mercure
// Capture aussi l'adresse IP pour l'audit trail RGPD
// =====================================================
namespace App\Service;

use App\Entity\ActionLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ActionLogService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private HubInterface $hub,
        private RequestStack $requestStack,
    ) {
    }

    // Enregistre une action et publie sur Mercure
    public function log(
        string $action,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null
    ): void {
        // Récupère l'email de l'utilisateur connecté
        $user = $this->security->getUser();
        $userEmail = $user ? $user->getUserIdentifier() : 'système';

        // Récupère l'adresse IP de la requête courante
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request ? $request->getClientIp() : null;

        $log = new ActionLog();
        $log->setAction($action);
        $log->setDescription($description);
        $log->setUserEmail($userEmail);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setIpAddress($ipAddress);
        $this->em->persist($log);
        $this->em->flush();

        // =====================
        // Publication Mercure — temps réel !
        // =====================
        try {
            $update = new Update(
                'https://project-manager.costincianu.fr/activity',
                json_encode([
                    'id' => $log->getId(),
                    'action' => $log->getAction(),
                    'description' => $log->getDescription(),
                    'userEmail' => $log->getUserEmail(),
                    'entityType' => $log->getEntityType(),
                    'entityId' => $log->getEntityId(),
                    'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
                ])
            );
            $this->hub->publish($update);
        } catch (\Exception $e) {
            // Si Mercure échoue, le log est quand même sauvegardé
        }
    }
}
