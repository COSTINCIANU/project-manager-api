<?php

// =====================================================
// SearchControllerTest.php — Tests PHPUnit
// Teste la recherche avancée avec filtres combinés
// Vérifie : terme, priorité, statut, projet, date
// =====================================================

namespace App\Tests\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    // Client HTTP pour les requêtes de test
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    // Gestionnaire de BDD
    private EntityManagerInterface $entityManager;

    // Token JWT récupéré à la connexion
    private string $token;

    // Identifiant du projet de test
    private int $projetId;

    // =====================
    // INITIALISATION AVANT CHAQUE TEST
    // =====================
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->nettoyerDonneesTest();
        $this->creerDonneesTest();
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

    // Supprime toutes les données créées pour les tests
    private function nettoyerDonneesTest(): void
    {
        // Supprime les tâches de test
        $taches = $this->entityManager->getRepository(Task::class)
            ->findBy(['name' => [
                'Tâche recherche haute priorité',
                'Tâche recherche basse priorité',
                'Tâche recherche terminée',
                'Tâche recherche en cours',
            ]]);
        foreach ($taches as $tache) {
            $this->entityManager->remove($tache);
        }

        // Supprime le projet de test
        $projet = $this->entityManager->getRepository(Project::class)
            ->findOneBy(['name' => 'Projet Test Recherche PHPUnit']);
        if ($projet) {
            $this->entityManager->remove($projet);
        }

        // Supprime l'utilisateur de test
        $utilisateur = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'test.search@example.com']);
        if ($utilisateur) {
            $this->entityManager->remove($utilisateur);
        }

        $this->entityManager->flush();
    }

    // Crée les données nécessaires pour les tests
    private function creerDonneesTest(): void
    {
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');

        // Crée l'utilisateur de test
        $utilisateur = new User();
        $utilisateur->setEmail('test.search@example.com');
        $utilisateur->setName('Test Search');
        $utilisateur->setRoles(['ROLE_USER']);
        $utilisateur->setPassword(
            $passwordHasher->hashPassword($utilisateur, 'MotDePasseTest123!')
        );
        $this->entityManager->persist($utilisateur);

        // Crée le projet de test
        $projet = new Project();
        $projet->setName('Projet Test Recherche PHPUnit');
        $projet->setStatus('En cours');
        $projet->setColor('#378ADD');
        $projet->setProgress(0);
        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        $this->projetId = $projet->getId();

        // Crée 4 tâches avec des caractéristiques différentes
        $tache1 = new Task();
        $tache1->setName('Tâche recherche haute priorité');
        $tache1->setPriority('haute');
        $tache1->setDone(false);
        $tache1->setInProgress(false);
        $tache1->setProjectId($this->projetId);
        $tache1->setDueDate('2026-12-31');
        $this->entityManager->persist($tache1);

        $tache2 = new Task();
        $tache2->setName('Tâche recherche basse priorité');
        $tache2->setPriority('basse');
        $tache2->setDone(false);
        $tache2->setInProgress(false);
        $tache2->setProjectId($this->projetId);
        $this->entityManager->persist($tache2);

        $tache3 = new Task();
        $tache3->setName('Tâche recherche terminée');
        $tache3->setPriority('normale');
        $tache3->setDone(true);
        $tache3->setInProgress(false);
        $tache3->setProjectId($this->projetId);
        $this->entityManager->persist($tache3);

        $tache4 = new Task();
        $tache4->setName('Tâche recherche en cours');
        $tache4->setPriority('normale');
        $tache4->setDone(false);
        $tache4->setInProgress(true);
        $tache4->setProjectId($this->projetId);
        $this->entityManager->persist($tache4);

        $this->entityManager->flush();
    }

    // Connexion et récupération du token JWT
    private function connecter(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.search@example.com',
                'password' => 'MotDePasseTest123!',
            ])
        );

        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $reponse['token'];
    }

    // =====================
    // TEST 1 — Recherche par terme
    // =====================
    public function testRechercheParTerme(): void
    {
        $this->client->request(
            'GET',
            '/api/search?q=recherche',
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Vérifie que la réponse contient les clés attendues
        $this->assertArrayHasKey('projects', $données);
        $this->assertArrayHasKey('tasks', $données);
        $this->assertArrayHasKey('total', $données);

        // Les 4 tâches de test contiennent "recherche"
        $this->assertGreaterThanOrEqual(4, count($données['tasks']),
            'Au moins 4 tâches doivent contenir le terme recherche');
    }

    // =====================
    // TEST 2 — Filtre par priorité
    // =====================
    public function testFiltreParPriorite(): void
    {
        $this->client->request(
            'GET',
            '/api/search?q=recherche&priority=haute',
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Toutes les tâches retournées doivent avoir la priorité haute
        foreach ($données['tasks'] as $tache) {
            $this->assertEquals('haute', $tache['priority'],
                'Toutes les tâches doivent avoir la priorité haute');
        }

        // Au moins notre tâche de test haute priorité doit être là
        $this->assertGreaterThanOrEqual(1, count($données['tasks']));
    }

    // =====================
    // TEST 3 — Filtre par statut terminé
    // =====================
    public function testFiltreParStatutTermine(): void
    {
        $this->client->request(
            'GET',
            '/api/search?q=recherche&status=done',
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Toutes les tâches retournées doivent être terminées
        foreach ($données['tasks'] as $tache) {
            $this->assertTrue($tache['done'],
                'Toutes les tâches doivent être terminées');
        }
    }

    // =====================
    // TEST 4 — Filtre par statut en cours
    // =====================
    public function testFiltreParStatutEnCours(): void
    {
        $this->client->request(
            'GET',
            '/api/search?q=recherche&status=in_progress',
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Toutes les tâches retournées doivent être en cours
        foreach ($données['tasks'] as $tache) {
            $this->assertTrue($tache['inProgress'],
                'Toutes les tâches doivent être en cours');
            $this->assertFalse($tache['done'],
                'Aucune tâche terminée ne doit apparaître');
        }
    }

    // =====================
    // TEST 5 — Filtre par projet
    // =====================
    public function testFiltreParProjet(): void
    {
        $this->client->request(
            'GET',
            "/api/search?q=recherche&project_id={$this->projetId}",
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Toutes les tâches doivent appartenir au projet de test
        foreach ($données['tasks'] as $tache) {
            $this->assertEquals($this->projetId, $tache['projectId'],
                'Toutes les tâches doivent appartenir au projet de test');
        }

        // Les 4 tâches de test doivent être présentes
        $this->assertCount(4, $données['tasks'],
            'Les 4 tâches du projet de test doivent être retournées');
    }

    // =====================
    // TEST 6 — Filtre par date
    // =====================
    public function testFiltreParDate(): void
    {
        $this->client->request(
            'GET',
            '/api/search?q=recherche&date_from=2026-12-01&date_to=2026-12-31',
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Seule la tâche avec dueDate=2026-12-31 doit apparaître
        $this->assertGreaterThanOrEqual(1, count($données['tasks']),
            'Au moins une tâche doit avoir une date dans cette plage');

        foreach ($données['tasks'] as $tache) {
            if ($tache['dueDate']) {
                $this->assertGreaterThanOrEqual('2026-12-01', $tache['dueDate']);
                $this->assertLessThanOrEqual('2026-12-31', $tache['dueDate']);
            }
        }
    }

    // =====================
    // TEST 7 — Sans filtre retourne vide
    // =====================
    public function testSansFiltreRetourneVide(): void
    {
        $this->client->request(
            'GET',
            '/api/search',
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEmpty($données['projects'], 'Sans filtre les projets doivent être vides');
        $this->assertEmpty($données['tasks'], 'Sans filtre les tâches doivent être vides');
        $this->assertEquals(0, $données['total']);
    }

    // =====================
    // TEST 8 — Route recherche tâches uniquement
    // =====================
    public function testRechercheTasksUniquement(): void
    {
        $this->client->request(
            'GET',
            "/api/search/tasks?project_id={$this->projetId}",
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('tasks', $données);
        $this->assertArrayHasKey('total', $données);
        $this->assertCount(4, $données['tasks'],
            'Les 4 tâches du projet doivent être retournées');
    }
}
