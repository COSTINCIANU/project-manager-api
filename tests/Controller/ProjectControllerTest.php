<?php
// =====================================================
// ProjectControllerTest.php — Tests des projets
// Teste les routes : lister, créer, supprimer
// Les routes projets nécessitent d'être connecté (JWT)
// =====================================================

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Project;

class ProjectControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    // S'exécute avant chaque test
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->nettoyerDonneesDeTest();
    }

    // S'exécute après chaque test
    protected function tearDown(): void
    {
        $this->nettoyerDonneesDeTest();
        parent::tearDown();
    }

    // Supprime les utilisateurs et projets créés pendant les tests
    private function nettoyerDonneesDeTest(): void
    {
        // Supprime les projets de test
        $projets = $this->entityManager
            ->getRepository(Project::class)
            ->findBy(['name' => 'Projet de test PHPUnit']);

        foreach ($projets as $projet) {
            $this->entityManager->remove($projet);
        }

        // Supprime les utilisateurs de test
        foreach (['admin.test@example.com', 'dev.test@example.com'] as $email) {
            $utilisateur = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $email]);

            if ($utilisateur) {
                $this->entityManager->remove($utilisateur);
            }
        }

        $this->entityManager->flush();
    }

    // Crée un utilisateur avec le rôle choisi et retourne son JWT
    private function creerUtilisateurEtSeConnecter(string $email, string $role): string
    {
        // Inscription
        $this->client->request(
            'POST',
            '/api/auth/register',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'MotDePasse123!',
                'role' => $role,
            ])
        );

        // On met à jour le rôle directement en BDD car register met 'dev' par défaut
        $utilisateur = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($utilisateur) {
            $utilisateur->setRole($role);
            $this->entityManager->flush();
        }

        // Connexion pour récupérer le JWT
        $this->client->request(
            'POST',
            '/api/auth/login',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'MotDePasse123!',
            ])
        );

        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        return $reponse['token'];
    }

    // =====================================================
    // TESTS LISTER LES PROJETS
    // =====================================================

    // Vérifie qu'un utilisateur connecté peut voir la liste des projets
    public function testListerLesProjetsConnecte(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('dev.test@example.com', 'dev');

        $this->client->request(
            'GET',
            '/api/projects',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseIsSuccessful();
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($reponse);
    }

    // Vérifie qu'on ne peut pas voir les projets sans être connecté
    public function testListerLesProjetsNonConnecte(): void
    {
        $this->client->request(
            'GET',
            '/api/projects',
            [], [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseStatusCodeSame(401);
    }

    // =====================================================
    // TESTS CRÉER UN PROJET
    // =====================================================

    // Vérifie qu'un admin peut créer un projet
    public function testCreerUnProjetEnTantQuAdmin(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('admin.test@example.com', 'admin');

        $this->client->request(
            'POST',
            '/api/projects',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'name' => 'Projet de test PHPUnit',
                'status' => 'en_cours',
                'color' => '#9B7FD4',
                'progress' => 0,
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $reponse);
        $this->assertEquals('Projet de test PHPUnit', $reponse['name']);
    }

    // Vérifie qu'un simple développeur ne peut pas créer un projet
    public function testCreerUnProjetEnTantQueDev(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('dev.test@example.com', 'dev');

        $this->client->request(
            'POST',
            '/api/projects',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'name' => 'Projet de test PHPUnit',
                'status' => 'en_cours',
                'color' => '#9B7FD4',
                'progress' => 0,
            ])
        );

        // Un dev ne peut pas créer un projet — accès refusé
        $this->assertResponseStatusCodeSame(403);
    }

    // =====================================================
    // TESTS SUPPRIMER UN PROJET
    // =====================================================

    // Vérifie qu'un admin peut supprimer un projet
    public function testSupprimerUnProjetEnTantQuAdmin(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('admin.test@example.com', 'admin');

        // On crée d'abord un projet à supprimer
        $this->client->request(
            'POST',
            '/api/projects',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'name' => 'Projet de test PHPUnit',
                'status' => 'en_cours',
                'color' => '#9B7FD4',
                'progress' => 0,
            ])
        );

        $projetCree = json_decode($this->client->getResponse()->getContent(), true);
        $idProjet = $projetCree['id'];

        // On supprime le projet
        $this->client->request(
            'DELETE',
            '/api/projects/' . $idProjet,
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseIsSuccessful();
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Projet supprimé avec succès', $reponse['message']);
    }

    // Vérifie qu'un dev ne peut pas supprimer un projet
    public function testSupprimerUnProjetEnTantQueDev(): void
    {
        // Un admin crée le projet
        $tokenAdmin = $this->creerUtilisateurEtSeConnecter('admin.test@example.com', 'admin');

        $this->client->request(
            'POST',
            '/api/projects',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokenAdmin,
            ],
            json_encode([
                'name' => 'Projet de test PHPUnit',
                'status' => 'en_cours',
                'color' => '#9B7FD4',
                'progress' => 0,
            ])
        );

        $projetCree = json_decode($this->client->getResponse()->getContent(), true);
        $idProjet = $projetCree['id'];

        // Un dev essaie de supprimer — accès refusé
        $tokenDev = $this->creerUtilisateurEtSeConnecter('dev.test@example.com', 'dev');

        $this->client->request(
            'DELETE',
            '/api/projects/' . $idProjet,
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokenDev,
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    // Vérifie qu'on ne peut pas supprimer un projet qui n'existe pas
    public function testSupprimerUnProjetInexistant(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('admin.test@example.com', 'admin');

        $this->client->request(
            'DELETE',
            '/api/projects/99999',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
