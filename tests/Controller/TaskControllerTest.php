<?php
// =====================================================
// TaskControllerTest.php — Tests des tâches
// Teste les routes : lister, créer, supprimer
// Les routes tâches nécessitent d'être connecté (JWT)
// =====================================================

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Task;

class TaskControllerTest extends WebTestCase
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

    // Supprime les tâches et utilisateurs créés pendant les tests
    private function nettoyerDonneesDeTest(): void
    {
        // Supprime les tâches de test
        $taches = $this->entityManager
            ->getRepository(Task::class)
            ->findBy(['name' => 'Tâche de test PHPUnit']);

        foreach ($taches as $tache) {
            $this->entityManager->remove($tache);
        }

        // Supprime les utilisateurs de test
        foreach (['admin.tache@example.com', 'dev.tache@example.com', 'client.tache@example.com'] as $email) {
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

        // On force le rôle directement en BDD
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
    // TESTS LISTER LES TÂCHES
    // =====================================================

    // Vérifie qu'un utilisateur connecté peut voir la liste des tâches
    public function testListerLesTachesConnecte(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('dev.tache@example.com', 'dev');

        $this->client->request(
            'GET',
            '/api/tasks',
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

    // Vérifie qu'on ne peut pas voir les tâches sans être connecté
    public function testListerLesTachesNonConnecte(): void
    {
        $this->client->request(
            'GET',
            '/api/tasks',
            [], [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseStatusCodeSame(401);
    }

    // =====================================================
    // TESTS CRÉER UNE TÂCHE
    // =====================================================

    // Vérifie qu'un dev peut créer une tâche
    public function testCreerUneTacheEnTantQueDev(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('dev.tache@example.com', 'dev');

        $this->client->request(
            'POST',
            '/api/tasks',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'name' => 'Tâche de test PHPUnit',
                'description' => 'Description de test',
                'priority' => 'normale',
                'projectId' => 1,
                'done' => false,
                'inProgress' => false,
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $reponse);
        $this->assertEquals('Tâche de test PHPUnit', $reponse['name']);
    }

    // Vérifie qu'un client ne peut pas créer une tâche
    public function testCreerUneTacheEnTantQueClient(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('client.tache@example.com', 'client');

        $this->client->request(
            'POST',
            '/api/tasks',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'name' => 'Tâche de test PHPUnit',
                'description' => 'Description de test',
                'priority' => 'normale',
                'projectId' => 1,
                'done' => false,
                'inProgress' => false,
            ])
        );

        // Un client ne peut pas créer une tâche — accès refusé
        $this->assertResponseStatusCodeSame(403);
    }

    // =====================================================
    // TESTS SUPPRIMER UNE TÂCHE
    // =====================================================

    // Vérifie qu'un manager peut supprimer une tâche
    public function testSupprimerUneTacheEnTantQueManager(): void
    {
        // On crée d'abord une tâche avec un dev
        $tokenDev = $this->creerUtilisateurEtSeConnecter('dev.tache@example.com', 'dev');

        $this->client->request(
            'POST',
            '/api/tasks',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokenDev,
            ],
            json_encode([
                'name' => 'Tâche de test PHPUnit',
                'priority' => 'normale',
                'projectId' => 1,
                'done' => false,
                'inProgress' => false,
            ])
        );

        $tacheCree = json_decode($this->client->getResponse()->getContent(), true);
        $idTache = $tacheCree['id'];

        // Un manager supprime la tâche
        $tokenManager = $this->creerUtilisateurEtSeConnecter('admin.tache@example.com', 'manager');

        $this->client->request(
            'DELETE',
            '/api/tasks/' . $idTache,
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokenManager,
            ]
        );

        $this->assertResponseIsSuccessful();
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Tâche supprimée avec succès', $reponse['message']);
    }

    // Vérifie qu'un client ne peut pas supprimer une tâche
    public function testSupprimerUneTacheEnTantQueClient(): void
    {
        // Un dev crée la tâche
        $tokenDev = $this->creerUtilisateurEtSeConnecter('dev.tache@example.com', 'dev');

        $this->client->request(
            'POST',
            '/api/tasks',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokenDev,
            ],
            json_encode([
                'name' => 'Tâche de test PHPUnit',
                'priority' => 'normale',
                'projectId' => 1,
                'done' => false,
                'inProgress' => false,
            ])
        );

        $tacheCree = json_decode($this->client->getResponse()->getContent(), true);
        $idTache = $tacheCree['id'];

        // Un client essaie de supprimer — accès refusé
        $tokenClient = $this->creerUtilisateurEtSeConnecter('client.tache@example.com', 'client');

        $this->client->request(
            'DELETE',
            '/api/tasks/' . $idTache,
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokenClient,
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    // Vérifie qu'on ne peut pas supprimer une tâche qui n'existe pas
    public function testSupprimerUneTacheInexistante(): void
    {
        $token = $this->creerUtilisateurEtSeConnecter('admin.tache@example.com', 'admin');

        $this->client->request(
            'DELETE',
            '/api/tasks/99999',
            [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
