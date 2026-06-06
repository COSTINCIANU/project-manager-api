<?php
// =====================================================
// MentionController.php — Gestion des mentions
// Détecte les @email dans les commentaires
// Envoie des notifications par email
// Permet de marquer les mentions comme lues
// =====================================================

namespace App\Controller;

use App\Entity\Mention;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mentions')]
class MentionController extends AbstractController
{
    // =====================
    // GET — Récupérer les mentions non lues de l'utilisateur connecté
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // On récupère les mentions non lues de l'utilisateur connecté
        $mentions = $em->getRepository(Mention::class)->findBy(
            ['mentionedEmail' => $user->getEmail(), 'isRead' => false],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function($mention) {
            return [
                'id' => $mention->getId(),
                'mentionedByEmail' => $mention->getMentionedByEmail(),
                'commentId' => $mention->getCommentId(),
                'taskId' => $mention->getTaskId(),
                'isRead' => $mention->isRead(),
                'createdAt' => $mention->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $mentions);

        return $this->json($data);
    }

    // =====================
    // POST — Créer des mentions depuis un commentaire
    // Détecte les @email dans le contenu
    // Envoie un email de notification
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';
        $commentId = $data['commentId'] ?? null;
        $taskId = $data['taskId'] ?? null;
        $taskName = $data['taskName'] ?? 'une tâche';

        // Détection des mentions @username dans le contenu
        // On cherche @texte (partie avant le @ de l'email)
        preg_match_all('/@([a-zA-Z0-9._\-]+)/', $content, $matches);

        $mentionedParts = array_unique($matches[1] ?? []);
        $created = [];

        foreach ($mentionedParts as $part) {
            // On cherche un utilisateur dont l'email commence par cette partie
            $users = $em->getRepository(User::class)->createQueryBuilder('u')
                ->where('u.email LIKE :part')
                ->setParameter('part', $part . '@%')
                ->getQuery()
                ->getResult();

            foreach ($users as $mentionedUser) {
                // On ne se mentionne pas soi-même
                if ($mentionedUser->getEmail() === $user->getEmail()) continue;

                // On crée la mention
                $mention = new Mention();
                $mention->setMentionedEmail($mentionedUser->getEmail());
                $mention->setMentionedByEmail($user->getEmail());
                $mention->setCommentId($commentId);
                $mention->setTaskId($taskId);
                $em->persist($mention);

                // Envoi de l'email de notification
                $emailMessage = (new Email())
                    ->from($_ENV['MAILER_FROM'])
                    ->to($mentionedUser->getEmail())
                    ->subject('Vous avez été mentionné — Project Manager')
                    ->html("
                        <div style='font-family:sans-serif;max-width:480px;margin:auto;padding:2rem;'>
                            <h2 style='font-size:18px;font-weight:600;'>💬 Vous avez été mentionné</h2>
                            <p><strong>{$user->getEmail()}</strong> vous a mentionné dans un commentaire sur la tâche <strong>{$taskName}</strong>.</p>
                            <a href='https://project-manager.costincianu.fr'
                            style='display:inline-block;padding:10px 20px;background:#111;color:#fff;border-radius:8px;text-decoration:none;margin-top:1rem;'>
                                Voir la tâche
                            </a>
                            <p style='color:#aaa;font-size:12px;margin-top:1rem;'>© 2026 Project Manager — COSTINCIANU Gheorghina</p>
                        </div>
                    ");

                $mailer->send($emailMessage);
                $created[] = $mentionedUser->getEmail();
            }
        }

        $em->flush();

        return $this->json([
            'message' => count($created) . ' mention(s) créée(s)',
            'mentioned' => $created,
        ]);
    }

    // =====================
    // PUT — Marquer une mention comme lue
    // =====================
    #[Route('/{id}/read', methods: ['PUT'])]
    public function markAsRead(int $id, EntityManagerInterface $em): JsonResponse
    {
        $mention = $em->getRepository(Mention::class)->find($id);

        if (!$mention) {
            return $this->json(['error' => 'Mention non trouvée'], 404);
        }

        // On marque la mention comme lue
        $mention->setIsRead(true);
        $em->flush();

        return $this->json(['message' => 'Mention marquée comme lue']);
    }

    // =====================
    // PUT — Marquer toutes les mentions comme lues
    // =====================
    #[Route('/read-all', methods: ['PUT'])]
    public function markAllAsRead(EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $mentions = $em->getRepository(Mention::class)->findBy([
            'mentionedEmail' => $user->getEmail(),
            'isRead' => false,
        ]);

        foreach ($mentions as $mention) {
            $mention->setIsRead(true);
        }

        $em->flush();

        return $this->json(['message' => count($mentions) . ' mention(s) marquée(s) comme lues']);
    }
}
