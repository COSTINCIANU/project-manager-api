<?php

// =====================================================
// AuthController.php — Authentification
// Gère l'inscription, connexion, profil,
// réinitialisation du mot de passe et refresh token
// =====================================================

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    // =====================
    // POST — Inscription
    // =====================
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existing) {
            return $this->json(['error' => 'Cet email est déjà utilisé'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));

        if (!empty($data['name'])) {
            $user->setName($data['name']);
        }

        $user->setRole($data['role'] ?? 'dev');

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Inscription réussie !',
            'email' => $user->getEmail(),
        ], 201);
    }

    // =====================
    // POST — Connexion
    // Retourne maintenant aussi le refresh token
    // =====================
    #[Route('/login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user || !$hasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        if ($user->isTwoFactorEnabled()) {
            return $this->json([
                'twoFactorRequired' => true,
                'email' => $user->getEmail(),
            ]);
        }

        // Génération du JWT (valide 1h par défaut lexik)
        $token = $jwtManager->create($user);

        // Génération du refresh token (token aléatoire valide 7 jours)
        $refreshToken = bin2hex(random_bytes(32));
        $user->setRefreshToken($refreshToken);
        $user->setRefreshTokenExpiry(new \DateTime('+7 days'));
        $em->flush();

        return $this->json([
            'message' => 'Connexion réussie !',
            'token' => $token,
            'refreshToken' => $refreshToken,
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'role' => in_array('ROLE_ADMIN', $user->getRoles()) ? 'admin' : $user->getRole(),
            'avatar' => $user->getAvatar(),
            'id' => $user->getId(),
        ]);
    }

    // =====================
    // POST — Renouvellement du JWT
    // Le mobile envoie son refresh token
    // On retourne un nouveau JWT si valide
    // =====================
    #[Route('/refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Vérification que le refresh token est bien présent
        if (empty($data['refreshToken'])) {
            return $this->json(['error' => 'Refresh token requis'], 400);
        }

        // On cherche l'utilisateur par son refresh token
        $user = $em->getRepository(User::class)->findOneBy([
            'refreshToken' => $data['refreshToken'],
        ]);

        // Token inconnu ou expiré
        if (!$user) {
            return $this->json(['error' => 'Refresh token invalide'], 401);
        }

        // Vérification de la date d'expiration (7 jours)
        if ($user->getRefreshTokenExpiry() < new \DateTime()) {
            // On efface le token expiré pour forcer une reconnexion
            $user->setRefreshToken(null);
            $user->setRefreshTokenExpiry(null);
            $em->flush();

            return $this->json(['error' => 'Refresh token expiré, veuillez vous reconnecter'], 401);
        }

        // Tout est OK — on génère un nouveau JWT
        $newToken = $jwtManager->create($user);

        // On renouvelle aussi le refresh token (rotation sécurité)
        $newRefreshToken = bin2hex(random_bytes(32));
        $user->setRefreshToken($newRefreshToken);
        $user->setRefreshTokenExpiry(new \DateTime('+7 days'));
        $em->flush();

        return $this->json([
            'token' => $newToken,
            'refreshToken' => $newRefreshToken,
        ]);
    }

    // =====================
    // GET — Profil utilisateur connecté
    // =====================
    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'role' => in_array('ROLE_ADMIN', $user->getRoles()) ? 'admin' : $user->getRole(),
            'avatar' => $user->getAvatar(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d'),
        ]);
    }

    // =====================
    // PUT — Modifier le profil
    // =====================
    #[Route('/profile', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['role'])) {
            $user->setRole($data['role']);
        }

        if (!empty($data['password'])) {
            $user->setPassword($hasher->hashPassword($user, $data['password']));
        }

        $em->flush();

        return $this->json([
            'message' => 'Profil mis à jour !',
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'role' => $user->getRole(),
        ]);
    }

    // =====================
    // POST — Réinitialisation mot de passe
    // Étape 1 : demande de réinitialisation
    // =====================
    #[Route('/forgot-password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json(['message' => 'Si cet email existe, un lien a été envoyé.']);
        }

        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiry(new \DateTime('+1 hour'));
        $em->flush();

        $resetLink = 'https://project-manager.costincianu.fr/reset-password?token='.$token;

        $email = (new Email())
            ->from('contact@costincianu.fr')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe — Project Manager')
            ->html("
                <div style='font-family: sans-serif; max-width: 500px; margin: 0 auto;'>
                    <h2 style='color: #111;'>🔐 Réinitialisation du mot de passe</h2>
                    <p>Bonjour,</p>
                    <p>Vous avez demandé à réinitialiser votre mot de passe sur <strong>Project Manager</strong>.</p>
                    <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                    <a href='{$resetLink}'
                    style='display: inline-block; padding: 12px 24px; background: #111; color: #fff;
                            border-radius: 8px; text-decoration: none; font-weight: 500; margin: 16px 0;'>
                        Réinitialiser mon mot de passe
                    </a>
                    <p style='color: #aaa; font-size: 12px;'>Ce lien expire dans 1 heure.</p>
                    <p style='color: #aaa; font-size: 12px;'>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='color: #aaa; font-size: 11px;'>© 2026 Project Manager — COSTINCIANU Gheorghina</p>
                </div>
            ");

        $mailer->send($email);

        return $this->json(['message' => 'Un email de réinitialisation a été envoyé !']);
    }

    // =====================
    // POST — Réinitialisation mot de passe
    // Étape 2 : nouveau mot de passe
    // =====================
    #[Route('/reset-password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['token']) || empty($data['password'])) {
            return $this->json(['error' => 'Token et mot de passe requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $data['token']]);

        if (!$user) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        if ($user->getResetTokenExpiry() < new \DateTime()) {
            return $this->json(['error' => 'Token expiré'], 400);
        }

        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setResetToken(null);
        $user->setResetTokenExpiry(null);
        $em->flush();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès !']);
    }

    // =====================
    // POST — Upload avatar
    // =====================
    #[Route('/avatar', methods: ['POST'])]
    public function uploadAvatar(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $file = $request->files->get('avatar');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier fourni'], 400);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->json(['error' => 'Format non supporté. Utilisez JPG, PNG ou GIF'], 400);
        }

        $filename = uniqid('avatar_').'.'.$file->guessExtension();

        $uploadDir = __DIR__.'/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $file->move($uploadDir, $filename);

        if ($user->getAvatar()) {
            $oldFile = $uploadDir.basename($user->getAvatar());
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        $avatarUrl = '/uploads/avatars/'.$filename;
        $user->setAvatar($avatarUrl);
        $em->flush();

        return $this->json([
            'message' => 'Avatar mis à jour !',
            'avatar' => $avatarUrl,
        ]);
    }
}
