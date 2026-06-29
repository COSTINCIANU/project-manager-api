<?php

// =====================================================
// ReportControllerTest.php — Tests PHPUnit
// Teste les routes de rapports avancés :
// vélocité, temps passé, multi-sprint, export CSV
// =====================================================

namespace App\Tests\Controller;

use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReportControllerTest extends WebTestCase
{
    // Client HTTP pour les requêtes de test
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    // Gestionnaire de BDD
    private EntityManagerInterface $entityManager;

    // Token JWT récupéré à la connexion
    private string $token;

    // Identifiant du projet de test
    private int $projetId;

    // Identifiant du sprint de test
    private int $sprintId;

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
        // Supprime les sprints de test
        $sprints = $this->entityManager->getRepository(Sprint::class)
            ->findBy(['name' => 'Sprint Test Rapports PHPUnit']);
        foreach ($sprints as $sprint) {
            $this->entityManager->remove($sprint);
        }

        // Supprime les tâches de test
        $taches = $this->entityManager->getRepository(Task::class)
            ->findBy(['name' => [
                'Tâche rapport haute 2h',
                'Tâche rapport normale 1h',
                'Tâche rapport terminée 3h',
            ]]);
        foreach ($taches as $tache) {
            $this->entityManager->remove($tache);
        }

        // Supprime le projet de test
        $projet = $this->entityManager->getRepository(Project::class)
            ->findOneBy(['name' => 'Projet Test Rapports PHPUnit']);
        if ($projet) {
            $this->entityManager->remove($projet);
        }

        // Supprime l'utilisateur de test
        $utilisateur = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'test.reports@example.com']);
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
        $utilisateur->setEmail('test.reports@example.com');
        $utilisateur->setName('Test Reports');
        $utilisateur->setRoles(['ROLE_USER']);
        $utilisateur->setPassword(
            $passwordHasher->hashPassword($utilisateur, 'MotDePasseTest123!')
        );
        $this->entityManager->persist($utilisateur);

        // Crée le projet de test
        $projet = new Project();
        $projet->setName('Projet Test Rapports PHPUnit');
        $projet->setStatus('En cours');
        $projet->setColor('#639922');
        $projet->setProgress(0);
        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        $this->projetId = $projet->getId();

        // Crée un sprint de test
        $sprint = new Sprint();
        $sprint->setName('Sprint Test Rapports PHPUnit');
        $sprint->setStatus('active');
        $sprint->setProjectId($this->projetId);
        $sprint->setStartDate('2026-06-01');
        $sprint->setEndDate('2026-06-14');
        $this->entityManager->persist($sprint);
        $this->entityManager->flush();

        $this->sprintId = $sprint->getId();

        // Crée 3 tâches avec des données variées
        $tache1 = new Task();
        $tache1->setName('Tâche rapport haute 2h');
        $tache1->setPriority('haute');
        $tache1->setDone(false);
        $tache1->setInProgress(true);
        $tache1->setProjectId($this->projetId);
        $tache1->setSprintId($this->sprintId);
        $tache1->setEstimatedTime(2);
        $this->entityManager->persist($tache1);

        $tache2 = new Task();
        $tache2->setName('Tâche rapport normale 1h');
        $tache2->setPriority('normale');
        $tache2->setDone(false);
        $tache2->setInProgress(false);
        $tache2->setProjectId($this->projetId);
        $tache2->setSprintId($this->sprintId);
        $tache2->setEstimatedTime(1);
        $this->entityManager->persist($tache2);

        $tache3 = new Task();
        $tache3->setName('Tâche rapport terminée 3h');
        $tache3->setPriority('basse');
        $tache3->setDone(true);
        $tache3->setInProgress(false);
        $tache3->setProjectId($this->projetId);
        $tache3->setSprintId($this->sprintId);
        $tache3->setEstimatedTime(3);
        $this->entityManager->persist($tache3);

        $this->entityManager->flush();
    }

    // Connexion et récupération du token JWT
    private function connecter(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.reports@example.com',
                'password' => 'MotDePasseTest123!',
            ])
        );

        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $reponse['token'];
    }

    // =====================
    // TEST 1 — Vélocité d'un projet
    // =====================
    public function testVelociteProjet(): void
    {
        $this->client->request(
            'GET',
            "/api/reports/project/{$this->projetId}/velocity",
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Vérifie la structure de la réponse
        $this->assertArrayHasKey('projetId', $données);
        $this->assertArrayHasKey('projetNom', $données);
        $this->assertArrayHasKey('sprints', $données);
        $this->assertArrayHasKey('velociteMoyenne', $données);
        $this->assertArrayHasKey('totalSprints', $données);

        // Vérifie que notre sprint de test est présent
        $this->assertGreaterThanOrEqual(1, count($données['sprints']));

        // Vérifie la structure d'un sprint
        $premierSprint = $données['sprints'][0];
        $this->assertArrayHasKey('sprintId', $premierSprint);
        $this->assertArrayHasKey('sprintNom', $premierSprint);
        $this->assertArrayHasKey('totalTaches', $premierSprint);
        $this->assertArrayHasKey('tachesTerminees', $premierSprint);
        $this->assertArrayHasKey('velocite', $premierSprint);
        $this->assertArrayHasKey('tauxCompletion', $premierSprint);
    }

    // =====================
    // TEST 2 — Temps passé par membre
    // =====================
    public function testTempsDepenseProjet(): void
    {
        $this->client->request(
            'GET',
            "/api/reports/project/{$this->projetId}/time-spent",
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Vérifie la structure
        $this->assertArrayHasKey('projetId', $données);
        $this->assertArrayHasKey('projetNom', $données);
        $this->assertArrayHasKey('totalHeures', $données);
        $this->assertArrayHasKey('parMembre', $données);
        $this->assertArrayHasKey('parTache', $données);

        // Vérifie que le total des heures est correct (2+1+3 = 6h)
        $this->assertEquals(6, $données['totalHeures'],
            'Le total des heures doit être 6 (2+1+3)');

        // Vérifie que les tâches sont triées par heures décroissantes
        $taches = $données['parTache'];
        if (count($taches) >= 2) {
            $this->assertGreaterThanOrEqual(
                $taches[1]['heuresEstimees'],
                $taches[0]['heuresEstimees'],
                'Les tâches doivent être triées par heures décroissantes'
            );
        }
    }

    // =====================
    // TEST 3 — Comparatif multi-sprints
    // =====================
    public function testMultiSprintProjet(): void
    {
        $this->client->request(
            'GET',
            "/api/reports/project/{$this->projetId}/multi-sprint",
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Vérifie la structure
        $this->assertArrayHasKey('projetId', $données);
        $this->assertArrayHasKey('projetNom', $données);
        $this->assertArrayHasKey('sprints', $données);
        $this->assertArrayHasKey('totalSprints', $données);

        // Vérifie qu'on a au moins notre sprint de test
        $this->assertGreaterThanOrEqual(1, count($données['sprints']));

        // Vérifie la structure d'un sprint dans le comparatif
        $sprint = $données['sprints'][0];
        $this->assertArrayHasKey('sprintId', $sprint);
        $this->assertArrayHasKey('totalTaches', $sprint);
        $this->assertArrayHasKey('tachesTerminees', $sprint);
        $this->assertArrayHasKey('tachesEnCours', $sprint);
        $this->assertArrayHasKey('tachesAFaire', $sprint);
        $this->assertArrayHasKey('tauxCompletion', $sprint);
        $this->assertArrayHasKey('parType', $sprint);
        $this->assertArrayHasKey('tempsEstime', $sprint);

        // Vérifie que la somme des tâches est correcte
        $totalCalcule = $sprint['tachesTerminees'] + $sprint['tachesEnCours'] + $sprint['tachesAFaire'];
        $this->assertEquals($sprint['totalTaches'], $totalCalcule,
            'La somme terminées+en cours+à faire doit égaler le total');
    }

    // =====================
    // TEST 4 — Export CSV
    // =====================
    public function testExportCsvProjet(): void
    {
        $this->client->request(
            'GET',
            "/api/reports/project/{$this->projetId}/export-csv",
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        // Vérifie que le Content-Type est bien CSV
        $this->assertResponseHeaderSame('content-type', 'text/csv; charset=UTF-8');

        $contenu = $this->client->getResponse()->getContent();

        // Vérifie que le CSV contient l'en-tête
        $this->assertStringContainsString('ID,Nom,Priorité', $contenu,
            'Le CSV doit contenir la ligne d\'en-tête');

        // Vérifie que les 3 tâches de test sont dans le CSV
        $this->assertStringContainsString('Tâche rapport haute 2h', $contenu);
        $this->assertStringContainsString('Tâche rapport normale 1h', $contenu);
        $this->assertStringContainsString('Tâche rapport terminée 3h', $contenu);

        // Vérifie que le CSV a au moins 4 lignes (1 entête + 3 tâches)
        $lignes = explode("\n", trim($contenu));
        $this->assertGreaterThanOrEqual(4, count($lignes),
            'Le CSV doit avoir au moins 4 lignes');
    }

    // =====================
    // TEST 5 — Projet inexistant retourne 404
    // =====================
    public function testProjetInexistantRetourne404(): void
    {
        $this->client->request(
            'GET',
            '/api/reports/project/99999/velocity',
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseStatusCodeSame(404);
    }

    // =====================
    // TEST 6 — Stats globales du projet
    // =====================
    public function testStatsGlobalesProjet(): void
    {
        $this->client->request(
            'GET',
            "/api/reports/project/{$this->projetId}/stats",
            [], [],
            ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $données = json_decode($this->client->getResponse()->getContent(), true);

        // Vérifie la structure
        $this->assertArrayHasKey('totalTaches', $données);
        $this->assertArrayHasKey('parStatut', $données);
        $this->assertArrayHasKey('parType', $données);
        $this->assertArrayHasKey('parPriorite', $données);

        // Vérifie le total — 3 tâches créées
        $this->assertEquals(3, $données['totalTaches'],
            'Le projet de test doit avoir 3 tâches');

        // Vérifie la répartition par statut
        $this->assertEquals(1, $données['parStatut']['terminees'],
            'Une tâche doit être terminée');
        $this->assertEquals(1, $données['parStatut']['enCours'],
            'Une tâche doit être en cours');
        $this->assertEquals(1, $données['parStatut']['aFaire'],
            'Une tâche doit être à faire');
    }
}
