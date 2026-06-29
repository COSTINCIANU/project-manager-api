<?php

// =====================================================
// CommentController.php — Gestion des commentaires
// Permet d'ajouter, modifier et supprimer des
// commentaires sur les tâches
// =====================================================

namespace App\Controller;

use App\Entity\Task;
use App\Entity\TaskComment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/tasks')]
class CommentController extends AbstractController
{
    // =====================
    // GET — Récupérer tous les commentaires d'une tâche
    // =====================
    #[Route('/{id}/comments', methods: ['GET'])]
    public function index(int $id, EntityManagerInterface $em): JsonResponse
    {
        // On cherche la tâche
        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        // On récupère tous les commentaires de la tâche
        $comments = $task->getComments()->toArray();

        $data = array_map(function ($comment) use ($em) {
            // On récupère l'email de l'utilisateur
            $user = $em->getRepository(User::class)->find($comment->getUserId());

            return [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'userId' => $comment->getUserId(),
                'userEmail' => $user ? $user->getEmail() : 'Inconnu',
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $comments);

        return $this->json($data);
    }

    // =====================
    // POST — Ajouter un commentaire
    // =====================
    #[Route('/{id}/comments', methods: ['POST'])]
    public function create(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
    ): JsonResponse {
        // On cherche la tâche
        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        // On récupère l'utilisateur connecté depuis le token JWT
        $token = $tokenStorage->getToken();
        // On cast explicitement en User pour avoir accès aux méthodes
        /** @var User $user */
        $user = $token ? $token->getUser() : null;

        $data = json_decode($request->getContent(), true);

        // On crée le commentaire
        $comment = new TaskComment();
        $comment->setContent($data['content']);
        $comment->setUserId($user instanceof User ? $user->getId() : null);
        $comment->setTask($task);

        $em->persist($comment);
        $em->flush();

        return $this->json([
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'userId' => $comment->getUserId(),
            'userEmail' => $user instanceof User ? $user->getEmail() : 'Inconnu',
            'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 201);
    }

    // =====================
    // DELETE — Supprimer un commentaire
    // =====================
    #[Route('/comments/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $comment = $em->getRepository(TaskComment::class)->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Commentaire non trouvé'], 404);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json(['message' => 'Commentaire supprimé']);
    }
}
