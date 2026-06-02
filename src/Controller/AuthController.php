<?php
// =====================================================
// AuthController.php — Authentification
// Gère l'inscription, connexion, profil
// et réinitialisation du mot de passe
// =====================================================

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

        // Nom optionnel à l'inscription
        if (!empty($data['name'])) {
            $user->setName($data['name']);
        }

        // Rôle par défaut : dev
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

        $token = $jwtManager->create($user);

        return $this->json([
            'message' => 'Connexion réussie !',
            'token' => $token,
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'role' => $user->getRole(),
            'avatar' => $user->getAvatar(),
            'id' => $user->getId(),
        ]);
    }

    // =====================
    // GET — Profil utilisateur connecté
    // =====================
    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'role' => $user->getRole(),
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
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour du nom
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        // Mise à jour du rôle
        if (isset($data['role'])) {
            $user->setRole($data['role']);
        }

        // Mise à jour du mot de passe
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
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        // On ne révèle pas si l'email existe ou non (sécurité)
        if (!$user) {
            return $this->json(['message' => 'Si cet email existe, un lien a été envoyé.']);
        }

        // Génère un token unique valable 1 heure
        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiry(new \DateTime('+1 hour'));

        $em->flush();

        // TODO : envoyer l'email avec le lien de réinitialisation
        // Pour l'instant on retourne le token pour les tests
        return $this->json([
            'message' => 'Lien de réinitialisation envoyé !',
            'token' => $token, // À retirer en production
        ]);
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

        // On cherche l'utilisateur par son token
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $data['token']]);

        if (!$user) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        // On vérifie que le token n'a pas expiré
        if ($user->getResetTokenExpiry() < new \DateTime()) {
            return $this->json(['error' => 'Token expiré'], 400);
        }

        // On met à jour le mot de passe
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setResetToken(null);
        $user->setResetTokenExpiry(null);

        $em->flush();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès !']);
    }
}
