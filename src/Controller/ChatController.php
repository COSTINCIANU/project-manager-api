<?php
// =====================================================
// ChatController.php — Chat d'équipe
// Gère les messages de chat avec polling
//
// MIGRATION WEBSOCKET FUTURE :
// 1. installer Mercure : composer require symfony/mercure-bundle
// 2. Publier sur le hub Mercure dans la méthode create()
// 3. Côté React : remplacer le polling par EventSource
//
// Exemple publication Mercure :
// $update = new Update(
//     'https://project-manager.costincianu.fr/chat',
//     json_encode(['message' => $data])
// );
// $this->hub->publish($update);
// =====================================================

namespace App\Controller;

use App\Entity\ChatMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chat')]
class ChatController extends AbstractController
{
    // =====================
    // GET — Récupérer les 50 derniers messages
    // Polling toutes les 5 secondes côté React
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        // On récupère les 50 derniers messages triés par date croissante
        $messages = $em->getRepository(ChatMessage::class)->findBy(
            [],
            ['createdAt' => 'ASC'],
            50
        );

        $data = array_map(function($message) {
            return [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'senderEmail' => $message->getSenderEmail(),
                'senderName' => $message->getSenderName(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $messages);

        return $this->json($data);
    }

    // =====================
    // POST — Envoyer un message
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['content'])) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        // On crée le message
        $message = new ChatMessage();
        $message->setContent($data['content']);
        $message->setSenderEmail($user->getEmail());
        $message->setSenderName($user->getName() ?? $user->getEmail());

        $em->persist($message);
        $em->flush();

        return $this->json([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'senderEmail' => $message->getSenderEmail(),
            'senderName' => $message->getSenderName(),
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 201);
    }

    // =====================
    // DELETE — Supprimer un message (admin uniquement)
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $message = $em->getRepository(ChatMessage::class)->find($id);

        if (!$message) {
            return $this->json(['error' => 'Message non trouvé'], 404);
        }

        $em->remove($message);
        $em->flush();

        return $this->json(['message' => 'Message supprimé']);
    }
}
