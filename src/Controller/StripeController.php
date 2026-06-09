<?php
// =====================================================
// StripeController.php — Paiement Stripe
// Gère les abonnements aux plans tarifaires
// Plans : Gratuit (0€) / Pro (9€/mois) / Entreprise (29€/mois)
// =====================================================

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;


use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stripe')]
class StripeController extends AbstractController
{
    // =====================
    // POST — Créer une session de paiement Stripe Checkout
    // =====================
    #[Route('/checkout', methods: ['POST'])]
    public function checkout(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $plan = $data['plan'] ?? 'pro';

        // Configuration Stripe
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // Prix selon le plan
        $prices = [
            'pro' => [
                'amount' => 900, // 9€ en centimes
                'name' => 'Project Manager Pro',
                'description' => 'Tâches illimitées, 5 membres, Assistant IA',
            ],
            'enterprise' => [
                'amount' => 2900, // 29€ en centimes
                'name' => 'Project Manager Entreprise',
                'description' => 'Tout le plan Pro + membres illimités + API publique',
            ],
        ];

        if (!isset($prices[$plan])) {
            return $this->json(['error' => 'Plan invalide'], 400);
        }

        $priceData = $prices[$plan];

        try {
            // Création de la session Stripe Checkout
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $priceData['name'],
                            'description' => $priceData['description'],
                        ],
                        'unit_amount' => $priceData['amount'],
                        'recurring' => [
                            'interval' => 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => 'https://project-manager.costincianu.fr?payment=success&plan=' . $plan,
                'cancel_url' => 'https://project-manager.costincianu.fr?payment=cancelled',
                'customer_email' => $user->getEmail(),
                'metadata' => [
                    'user_email' => $user->getEmail(),
                    'plan' => $plan,
                ],
            ]);

            return $this->json([
                'sessionId' => $session->id,
                'url' => $session->url,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }


    // =====================
    // POST — Webhook Stripe
    // Reçoit les événements de paiement de Stripe
    // =====================
    #[Route('/webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        try {
            if ($webhookSecret) {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $webhookSecret
                );
            } else {
                $event = \Stripe\Event::constructFrom(
                    json_decode($payload, true)
                );
            }

            // Gestion des événements
            switch ($event->type) {
                case 'checkout.session.completed':
                    // Paiement réussi — activer l'abonnement
                    $session = $event->data->object;
                    $userEmail = $session->metadata->user_email ?? null;
                    $plan = $session->metadata->plan ?? null;

                    if ($userEmail && $plan) {
                        $user = $em->getRepository(User::class)->findOneBy(['email' => $userEmail]);
                        if ($user) {
                            $user->setPlan($plan);
                            $em->flush();
                        }
                    }
                    break;

                case 'customer.subscription.deleted':
                    // Abonnement annulé — repasser en gratuit
                    $subscription = $event->data->object;
                    $customerId = $subscription->customer;
                    // On cherche l'utilisateur par customer_id si stocké
                    break;
            }

            return $this->json(['received' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // =====================
    // GET — Retourner la clé publique Stripe
    // =====================
    #[Route('/public-key', methods: ['GET'])]
    public function publicKey(): JsonResponse
    {
        return $this->json([
            'publicKey' => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
        ]);
    }
}
