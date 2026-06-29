<?php

// =====================================================
// ActionLogController.php — Historique des actions
// Retourne les dernières actions enregistrées
// =====================================================

namespace App\Controller;

use App\Entity\ActionLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/logs')]
class ActionLogController extends AbstractController
{
    // =====================
    // GET — Récupérer les dernières actions
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        // On récupère les 50 dernières actions triées par date décroissante
        $logs = $em->getRepository(ActionLog::class)->findBy(
            [],
            ['createdAt' => 'DESC'],
            50
        );

        $data = array_map(function ($log) {
            return [
                'id' => $log->getId(),
                'action' => $log->getAction(),
                'description' => $log->getDescription(),
                'userEmail' => $log->getUserEmail(),
                'entityType' => $log->getEntityType(),
                'entityId' => $log->getEntityId(),
                'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $logs);

        return $this->json($data);
    }
}
