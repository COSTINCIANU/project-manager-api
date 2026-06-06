<?php
// =====================================================
// PushController.php — Notifications Push
// Gère les abonnements et l'envoi de notifications
// push via l'API Web Push (VAPID)
// =====================================================

namespace App\Controller;

use App\Entity\PushSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/push')]
class PushController extends AbstractController
{
    // =====================
    // GET — Retourner la clé publique VAPID
    // Nécessaire pour que le navigateur s'abonne
    // =====================
    #[Route('/vapid-key', methods: ['GET'])]
    public function vapidKey(): JsonResponse
    {
        return $this->json([
            'publicKey' => $_ENV['VAPID_PUBLIC_KEY'],
        ]);
    }

    // =====================
    // POST — Enregistrer un abonnement push
    // Appelé quand l'utilisateur autorise les notifications
    // =====================
    #[Route('/subscribe', methods: ['POST'])]
    public function subscribe(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        // On vérifie que l'abonnement n'existe pas déjà
        $existing = $em->getRepository(PushSubscription::class)->findOneBy([
            'endpoint' => $data['endpoint'],
        ]);

        if ($existing) {
            return $this->json(['message' => 'Abonnement déjà enregistré']);
        }

        // On enregistre le nouvel abonnement
        $subscription = new PushSubscription();
        $subscription->setUserEmail($user->getEmail());
        $subscription->setEndpoint($data['endpoint']);
        $subscription->setP256dh($data['keys']['p256dh']);
        $subscription->setAuth($data['keys']['auth']);

        $em->persist($subscription);
        $em->flush();

        return $this->json(['message' => 'Abonnement enregistré !'], 201);
    }

    // =====================
    // POST — Envoyer une notification push à un utilisateur
    // Appelé par d'autres controllers (mentions, chat, etc.)
    // =====================
    #[Route('/send', methods: ['POST'])]
    public function send(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $targetEmail = $data['email'] ?? null;
        $title = $data['title'] ?? 'Project Manager';
        $body = $data['body'] ?? 'Vous avez une nouvelle notification';
        $url = $data['url'] ?? 'https://project-manager.costincianu.fr';

        if (!$targetEmail) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        // On récupère tous les abonnements de l'utilisateur
        $subscriptions = $em->getRepository(PushSubscription::class)->findBy([
            'userEmail' => $targetEmail,
        ]);

        if (empty($subscriptions)) {
            return $this->json(['message' => 'Aucun abonnement trouvé']);
        }

        // Configuration WebPush avec les clés VAPID
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $_ENV['VAPID_SUBJECT'],
                'publicKey' => $_ENV['VAPID_PUBLIC_KEY'],
                'privateKey' => $_ENV['VAPID_PRIVATE_KEY'],
            ],
        ]);

        // Payload de la notification
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => 'https://project-manager.costincianu.fr/favicon.svg',
        ]);

        // Envoi à tous les appareils de l'utilisateur
        $sent = 0;
        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->getEndpoint(),
                'keys' => [
                    'p256dh' => $sub->getP256dh(),
                    'auth' => $sub->getAuth(),
                ],
            ]);

            $webPush->queueNotification($subscription, $payload);
            $sent++;
        }

        // Flush — envoie toutes les notifications en attente
        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                // Si l'abonnement est expiré on le supprime
                $expiredSub = $em->getRepository(PushSubscription::class)->findOneBy([
                    'endpoint' => $report->getEndpoint(),
                ]);
                if ($expiredSub) {
                    $em->remove($expiredSub);
                }
            }
        }

        $em->flush();

        return $this->json(['message' => $sent . ' notification(s) envoyée(s)']);
    }

    // =====================
    // DELETE — Supprimer un abonnement push
    // Appelé quand l'utilisateur désactive les notifications
    // =====================
    #[Route('/unsubscribe', methods: ['DELETE'])]
    public function unsubscribe(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $endpoint = $data['endpoint'] ?? null;

        if (!$endpoint) {
            return $this->json(['error' => 'Endpoint requis'], 400);
        }

        $subscription = $em->getRepository(PushSubscription::class)->findOneBy([
            'endpoint' => $endpoint,
        ]);

        if ($subscription) {
            $em->remove($subscription);
            $em->flush();
        }

        return $this->json(['message' => 'Abonnement supprimé']);
    }
}
