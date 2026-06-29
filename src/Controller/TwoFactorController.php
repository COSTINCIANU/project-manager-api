<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class TwoFactorController extends AbstractController
{
    // =====================
    // ENVOYER LE CODE 2FA
    // =====================
    #[Route('/api/auth/2fa/send', name: 'auth_2fa_send', methods: ['POST'])]
    public function send(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['error' => 'Email requis'], 400);
        }

        // On cherche l'utilisateur
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['message' => 'Si cet email existe, un code a été envoyé.']);
        }

        // Génération du code à 6 chiffres
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Expiration dans 10 minutes
        $expiresAt = new \DateTime('+10 minutes');

        $user->setTwoFactorCode($code);
        $user->setTwoFactorExpiresAt($expiresAt);
        $em->flush();

        // Envoi de l'email
        $emailMessage = (new Email())
            ->from($_ENV['MAILER_FROM'])
            ->to($email)
            ->subject('Votre code de connexion — Project Manager')
            ->html("<div
                        style='font-family:sans-serif;max-width:480px;margin:auto;padding:2rem;'>
                        <h2 style='font-size:20px;font-weight:600;'>🔐 Code de vérification</h2>
                        <p>Votre code de connexion est :</p>
                        <div style='font-size:36px;font-weight:700;letter-spacing:8px;color:#111;margin:1.5rem 0;'>
                            {$code}
                        </div>
                        <p style='color:#888;font-size:13px;'>Ce code expire dans 10 minutes.</p>
                        <p style='color:#888;font-size:13px;'>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                    </div>
                ");

        $mailer->send($emailMessage);

        return new JsonResponse(['message' => 'Si cet email existe, un code a été envoyé.']);
    }

    // =====================
    // VÉRIFIER LE CODE 2FA
    // =====================
    #[Route('/api/auth/2fa/verify', name: 'auth_2fa_verify', methods: ['POST'])]
    public function verify(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $code = $data['code'] ?? null;

        if (!$email || !$code) {
            return new JsonResponse(['error' => 'Email et code requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        // Vérification du code et de l'expiration
        if ($user->getTwoFactorCode() !== $code) {
            return new JsonResponse(['error' => 'Code invalide'], 401);
        }

        if ($user->getTwoFactorExpiresAt() < new \DateTime()) {
            return new JsonResponse(['error' => 'Code expiré'], 401);
        }

        // On efface le code après utilisation
        $user->setTwoFactorCode(null);
        $user->setTwoFactorExpiresAt(null);
        $em->flush();

        // Génération du token JWT
        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'email' => $user->getEmail(),
        ]);
    }

    // =====================
    // ACTIVER / DÉSACTIVER LE 2FA
    // =====================
    #[Route('/api/auth/2fa/toggle', name: 'auth_2fa_toggle', methods: ['POST'])]
    public function toggle(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $enabled = $data['enabled'] ?? false;

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        $user->setTwoFactorEnabled($enabled);
        $em->flush();

        return new JsonResponse(['message' => '2FA '.($enabled ? 'activé' : 'désactivé')]);
    }
}
