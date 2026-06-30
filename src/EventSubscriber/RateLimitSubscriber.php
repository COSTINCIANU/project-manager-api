<?php
// =====================================================
// RateLimitSubscriber.php — Limitation de débit API
// Applique le rate limiting sur toutes les requêtes API
// Retourne 429 Too Many Requests si dépassement
// =====================================================

namespace App\EventSubscriber;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    public function __construct(
        private RateLimiterFactory $apiGlobalLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Applique le rate limiting uniquement sur les routes API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Identifie le client par son IP
        $ip = $request->getClientIp() ?? 'unknown';
        $limiter = $this->apiGlobalLimiter->create($ip);
        $limit = $limiter->consume(1);

        // Ajoute les headers informatifs sur le quota
        $event->getRequest()->attributes->set('rate_limit_remaining', $limit->getRemainingTokens());

        if (!$limit->isAccepted()) {
            $response = new JsonResponse([
                'error' => 'Trop de requêtes — veuillez réessayer dans quelques instants.',
                'retryAfter' => $limit->getRetryAfter()->getTimestamp() - time(),
            ], 429);

            $event->setResponse($response);
        }
    }
}
