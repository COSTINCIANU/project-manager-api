<?php

// =====================================================
// RegleAutomatisationControllerTest.php — Tests PHPUnit
// Teste les routes CRUD des règles d'automatisation
// Crée un utilisateur et un projet de test à la volée
// =====================================================

namespace App\Tests\Controller;

use App\Entity\Project;
use App\Entity\RegleAutomatisation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegleAutomatisationControllerTest extends WebTestCase
{
    // Client HTTP pour les requêtes de test
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    // Gestionnaire de BDD pour créer et nettoyer les données de test
    private EntityManagerInterface $entityManager;

    // Token JWT récupéré à la connexion
    private string $token;

    // Identifiant du projet créé pour les tests
    private int $projetId;

    // =====================
    // INITIALISATION AVANT CHAQUE TEST
    // Crée un utilisateur et un projet de test en BDD
    // =====================
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->nettoyerDonneesTest();
        $this->creerUtilisateurTest();
        $this->creerProjetTest();
        $this->connecter();
    }

    // =====================
    // NETTOYAGE APRÈS CHAQUE TEST
    // =====================
    protected function tearDown(): void
    {
        $this->nettoyerDonneesTest();
        parent::tearDown();
    }

    // Supprime les données de test créées pour ce test
    private function nettoyerDonneesTest(): void
    {
        // Supprime les règles de test
        $regles = $this->entityManager
            ->getRepository(RegleAutomatisation::class)
            ->findAll();
        foreach ($regles as $regle) {
            $this->entityManager->remove($regle);
        }

        // Supprime le projet de test
        $projet = $this->entityManager
            ->getRepository(Project::class)
            ->findOneBy(['name' => 'Projet Test PHPUnit Regles']);
        if ($projet) {
            $this->entityManager->remove($projet);
        }

        // Supprime l'utilisateur de test
        $utilisateur = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'test.regles@example.com']);
        if ($utilisateur) {
            $this->entityManager->remove($utilisateur);
        }

        $this->entityManager->flush();
    }

    // Crée un utilisateur de test avec mot de passe hashé
    private function creerUtilisateurTest(): void
    {
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');

        $utilisateur = new User();
        $utilisateur->setEmail('test.regles@example.com');
        $utilisateur->setName('Test Regles');
        $utilisateur->setRoles(['ROLE_USER']);
        $utilisateur->setPassword(
            $passwordHasher->hashPassword($utilisateur, 'MotDePasseTest123!')
        );

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();
    }

    // Crée un projet de test pour rattacher les règles
    private function creerProjetTest(): void
    {
        $projet = new Project();
        $projet->setName('Projet Test PHPUnit Regles');
        $projet->setStatus('En cours');
        $projet->setColor('#378ADD');
        $projet->setProgress(0);

        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        // Sauvegarde l'identifiant pour les tests
        $this->projetId = $projet->getId();
    }

    // Connexion et récupération du token JWT
    private function connecter(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.regles@example.com',
                'password' => 'MotDePasseTest123!',
            ])
        );

        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $reponse['token'];
    }

    // =====================
    // TEST 1 — Liste des règles d'un projet
    // =====================
    public function testListeReglesProjet(): void
    {
        $this->client->request(
            'GET',
            "/api/projects/{$this->projetId}/regles",
            [],
            [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($données, 'La liste des règles doit être un tableau');
    }

    // =====================
    // TEST 2 — Création d'une règle
    // =====================
    public function testCreerRegle(): void
    {
        $this->client->request(
            'POST',
            "/api/projects/{$this->projetId}/regles",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer {$this->token}",
            ],
            json_encode([
                'nom' => 'Test PHPUnit — règle automatique',
                'declencheur' => 'tache_statut_change',
                'valeurDeclencheur' => 'Terminé',
                'action' => 'changer_priorite',
                'valeurAction' => 'haute',
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $données = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $données, 'La règle doit avoir un identifiant');
        $this->assertArrayHasKey('nom', $données, 'La règle doit avoir un nom');
        $this->assertArrayHasKey('declencheur', $données, 'La règle doit avoir un déclencheur');
        $this->assertArrayHasKey('action', $données, 'La règle doit avoir une action');
        $this->assertArrayHasKey('active', $données, 'La règle doit avoir un état actif');
        $this->assertEquals('Test PHPUnit — règle automatique', $données['nom']);
        $this->assertEquals('tache_statut_change', $données['declencheur']);
        $this->assertEquals('changer_priorite', $données['action']);
        $this->assertTrue($données['active'], 'Une nouvelle règle doit être active par défaut');
    }

    // =====================
    // TEST 3 — Création sans les champs obligatoires
    // =====================
    public function testCreerRegleSansChampObligatoire(): void
    {
        $this->client->request(
            'POST',
            "/api/projects/{$this->projetId}/regles",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer {$this->token}",
            ],
            json_encode(['valeurAction' => 'haute'])
        );

        $this->assertResponseStatusCodeSame(400);

        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('erreur', $données, 'Un message d\'erreur doit être retourné');
    }

    // =====================
    // TEST 4 — Toggle activer et désactiver une règle
    // =====================
    public function testToggleRegle(): void
    {
        // Crée une règle à toggler
        $this->client->request(
            'POST',
            "/api/projects/{$this->projetId}/regles",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer {$this->token}",
            ],
            json_encode([
                'nom' => 'Test toggle',
                'declencheur' => 'tache_creee',
                'action' => 'notifier_manager',
            ])
        );

        $regle = json_decode($this->client->getResponse()->getContent(), true);
        $regleId = $regle['id'];

        // Toggle — doit passer de active=true à active=false
        $this->client->request(
            'PATCH',
            "/api/regles/{$regleId}/toggle",
            [],
            [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($données['active'], 'La règle doit être désactivée après le toggle');
    }

    // =====================
    // TEST 5 — Suppression d'une règle
    // =====================
    public function testSupprimerRegle(): void
    {
        // Crée une règle à supprimer
        $this->client->request(
            'POST',
            "/api/projects/{$this->projetId}/regles",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer {$this->token}",
            ],
            json_encode([
                'nom' => 'Test suppression',
                'declencheur' => 'tache_creee',
                'action' => 'notifier_manager',
            ])
        );

        $regle = json_decode($this->client->getResponse()->getContent(), true);
        $regleId = $regle['id'];

        // Supprime la règle
        $this->client->request(
            'DELETE',
            "/api/regles/{$regleId}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        // Vérifie que la règle n'est plus dans la liste
        $this->client->request(
            'GET',
            "/api/projects/{$this->projetId}/regles",
            [],
            [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $regles = json_decode($this->client->getResponse()->getContent(), true);
        $idsRestants = array_column($regles, 'id');
        $this->assertNotContains($regleId, $idsRestants, 'La règle supprimée ne doit plus être dans la liste');
    }

    // =====================
    // TEST 6 — Projet inexistant retourne 404
    // =====================
    public function testReglesProjetInexistant(): void
    {
        $this->client->request(
            'GET',
            '/api/projects/99999/regles',
            [],
            [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
