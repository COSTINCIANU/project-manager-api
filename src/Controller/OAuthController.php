<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    // =====================
    // GITHUB
    // =====================

    #[Route('/api/auth/github', name: 'auth_github_start', methods: ['GET'])]
    public function githubStart(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Redirige vers GitHub pour l'autorisation
        return $clientRegistry->getClient('github')->redirect(['user:email'], []);
    }

    #[Route('/api/auth/github/callback', name: 'auth_github_callback', methods: ['GET'])]
    public function githubCallback(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        Request $request
    ): RedirectResponse {
        try {
            $client = $clientRegistry->getClient('github');
            $githubUser = $client->fetchUser();

            $email = $githubUser->getEmail();

            // Si pas d'email public sur GitHub
            if (!$email) {
                return new RedirectResponse(
                    'https://project-manager.costincianu.fr?oauth_error=no_email'
                );
            }

            // On cherche l'utilisateur en BDD ou on le crée
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setRoles(['ROLE_USER']);
                // Mot de passe aléatoire car connexion OAuth
                $user->setPassword(
                    $passwordHasher->hashPassword($user, bin2hex(random_bytes(16)))
                );
                $em->persist($user);
                $em->flush();
            }

            // Génération du token JWT
            $token = $jwtManager->create($user);

            // Redirection vers le frontend avec le token
            return new RedirectResponse(
                'https://project-manager.costincianu.fr?oauth_token='.$token.'&oauth_email='.urlencode($email)
            );
        } catch (\Exception $e) {
            return new RedirectResponse(
                'https://project-manager.costincianu.fr?oauth_error='.urlencode($e->getMessage())
            );
        }
    }

    // =====================
    // GOOGLE
    // =====================

    #[Route('/api/auth/google', name: 'auth_google_start', methods: ['GET'])]
    public function googleStart(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Redirige vers Google pour l'autorisation
        return $clientRegistry->getClient('google')->redirect(['email', 'profile'], []);
    }

    #[Route('/api/auth/google/callback', name: 'auth_google_callback', methods: ['GET'])]
    public function googleCallback(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        Request $request
    ): RedirectResponse {
        try {
            $client = $clientRegistry->getClient('google');
            $googleUser = $client->fetchUser();

            $email = $googleUser->getEmail();

            // On cherche l'utilisateur en BDD ou on le crée
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setRoles(['ROLE_USER']);
                // Mot de passe aléatoire car connexion OAuth
                $user->setPassword(
                    $passwordHasher->hashPassword($user, bin2hex(random_bytes(16)))
                );
                $em->persist($user);
                $em->flush();
            }

            // Génération du token JWT
            $token = $jwtManager->create($user);

            // Redirection vers le frontend avec le token
            return new RedirectResponse(
                'https://project-manager.costincianu.fr?oauth_token='.$token.'&oauth_email='.urlencode($email)
            );
        } catch (\Exception $e) {
            return new RedirectResponse(
                'https://project-manager.costincianu.fr?oauth_error='.urlencode($e->getMessage())
            );
        }
    }
}
