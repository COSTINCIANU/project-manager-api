<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    // =====================
    // POST — Inscription d'un nouvel utilisateur
    // =====================
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        // On récupère les données envoyées par React
        $data = json_decode($request->getContent(), true);

        // On vérifie que l'email et le mot de passe sont présents
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        // On vérifie que l'email n'est pas déjà utilisé
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existing) {
            return $this->json(['error' => 'Cet email est déjà utilisé'], 400);
        }

        // On crée un nouvel utilisateur
        $user = new User();
        $user->setEmail($data['email']);

        // On hash le mot de passe pour la sécurité
        $hashedPassword = $hasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // On sauvegarde dans la base de données
        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Inscription réussie !',
            'email' => $user->getEmail(),
        ], 201);
    }

    // =====================
    // POST — Connexion d'un utilisateur
    // =====================
    #[Route('/login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        // On récupère les données envoyées par React
        $data = json_decode($request->getContent(), true);

        // On vérifie que l'email et le mot de passe sont présents
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        // On cherche l'utilisateur par son email
        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        // Si l'utilisateur n'existe pas ou le mot de passe est incorrect
        if (!$user || !$hasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        // On génère le token JWT
        $token = $jwtManager->create($user);

        return $this->json([
            'message' => 'Connexion réussie !',
            'token' => $token,
            'email' => $user->getEmail(),
        ]);
    }

    // =====================
    // GET — Récupérer le profil de l'utilisateur connecté
    // =====================
    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        // On récupère l'utilisateur connecté depuis le token JWT
        $user = $this->getUser();

        // Si pas d'utilisateur connecté
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json([
            'email' => $user->getUserIdentifier(),
        ]);
    }
}
