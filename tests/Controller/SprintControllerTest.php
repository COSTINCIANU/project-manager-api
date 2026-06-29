<?php

// =====================================================
// SprintControllerTest.php — Tests PHPUnit
// Teste les routes Sprint :
// GET par projet, GET backlog, POST create,
// PUT update, POST assign-task, POST remove-task, DELETE
// =====================================================

namespace App\Tests\Controller;

use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SprintControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $token;
    private int $projetId;
    private int $sprintId;
    private int $tacheId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->nettoyerDonneesTest();
        $this->creerDonneesTest();
        $this->connecter();
    }

    protected function tearDown(): void
    {
        $this->nettoyerDonneesTest();
        parent::tearDown();
    }

    private function nettoyerDonneesTest(): void
    {
        $taches = $this->entityManager->getRepository(Task::class)
            ->findBy(['name' => 'Tâche Sprint PHPUnit']);
        foreach ($taches as $t) {
            $this->entityManager->remove($t);
        }

        $sprints = $this->entityManager->getRepository(Sprint::class)
            ->findBy(['name' => 'Sprint PHPUnit Test']);
        foreach ($sprints as $s) {
            $this->entityManager->remove($s);
        }

        $projet = $this->entityManager->getRepository(Project::class)
            ->findOneBy(['name' => 'Projet Sprint PHPUnit']);
        if ($projet) {
            $this->entityManager->remove($projet);
        }

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'test.sprint@example.com']);
        if ($user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
    }

    private function creerDonneesTest(): void
    {
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail('test.sprint@example.com');
        $user->setName('Test Sprint');
        $user->setRoles(['ROLE_USER']);
        $user->setRole('admin');
        $user->setPassword($passwordHasher->hashPassword($user, 'MotDePasseTest123!'));
        $this->entityManager->persist($user);

        $projet = new Project();
        $projet->setName('Projet Sprint PHPUnit');
        $projet->setStatus('En cours');
        $projet->setColor('#378ADD');
        $projet->setProgress(0);
        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        $this->projetId = $projet->getId();

        $sprint = new Sprint();
        $sprint->setName('Sprint PHPUnit Test');
        $sprint->setStatus('planifie');
        $sprint->setProjectId($this->projetId);
        $sprint->setStartDate('2026-07-01');
        $sprint->setEndDate('2026-07-14');
        $this->entityManager->persist($sprint);
        $this->entityManager->flush();

        $this->sprintId = $sprint->getId();

        $tache = new Task();
        $tache->setName('Tâche Sprint PHPUnit');
        $tache->setPriority('normale');
        $tache->setDone(false);
        $tache->setInProgress(false);
        $tache->setProjectId($this->projetId);
        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $this->tacheId = $tache->getId();
    }

    private function connecter(): void
    {
        $this->client->request(
            'POST', '/api/auth/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test.sprint@example.com', 'password' => 'MotDePasseTest123!'])
        );
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $reponse['token'];
    }

    // =====================
    // TEST 1 — GET sprints par projet
    // =====================
    public function testGetSprintsParProjet(): void
    {
        $this->client->request(
            'GET', "/api/sprints/project/{$this->projetId}",
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($données);
        $this->assertGreaterThanOrEqual(1, count($données));
    }

    // =====================
    // TEST 2 — GET backlog du projet
    // =====================
    public function testGetBacklogProjet(): void
    {
        $this->client->request(
            'GET', "/api/sprints/project/{$this->projetId}/backlog",
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($données);
    }

    // =====================
    // TEST 3 — POST créer un sprint
    // =====================
    public function testCreerSprint(): void
    {
        $this->client->request(
            'POST', '/api/sprints',
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode([
                'name' => 'Sprint PHPUnit Test',
                'status' => 'planifie',
                'projectId' => $this->projetId,
                'startDate' => '2026-08-01',
                'endDate' => '2026-08-14',
            ])
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $données);
        $this->assertEquals('Sprint PHPUnit Test', $données['name']);

        // Nettoyage du sprint créé
        $sprint = $this->entityManager->getRepository(Sprint::class)->find($données['id']);
        if ($sprint) {
            $this->entityManager->remove($sprint);
            $this->entityManager->flush();
        }
    }

    // =====================
    // TEST 4 — PUT mettre à jour un sprint
    // =====================
    public function testMettreAJourSprint(): void
    {
        $this->client->request(
            'PUT', "/api/sprints/{$this->sprintId}",
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode(['status' => 'active', 'name' => 'Sprint PHPUnit Test'])
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('active', $données['status']);
    }

    // =====================
    // TEST 5 — POST assigner une tâche au sprint
    // =====================
    public function testAssignerTacheAuSprint(): void
    {
        $this->client->request(
            'POST', "/api/sprints/{$this->sprintId}/assign-task",
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode(['taskId' => $this->tacheId])
        );

        $this->assertResponseIsSuccessful();
    }

    // =====================
    // TEST 6 — POST retirer une tâche du sprint
    // =====================
    public function testRetirerTacheDuSprint(): void
    {
        // D'abord assigner la tâche
        $this->client->request(
            'POST', "/api/sprints/{$this->sprintId}/assign-task",
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode(['taskId' => $this->tacheId])
        );

        // Puis la retirer
        $this->client->request(
            'POST', "/api/sprints/{$this->sprintId}/remove-task",
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode(['taskId' => $this->tacheId])
        );

        $this->assertResponseIsSuccessful();
    }

    // =====================
    // TEST 7 — DELETE supprimer un sprint
    // =====================
    public function testSupprimerSprint(): void
    {
        // Crée un sprint temporaire pour le supprimer
        $sprint = new Sprint();
        $sprint->setName('Sprint PHPUnit Test');
        $sprint->setStatus('planifie');
        $sprint->setProjectId($this->projetId);
        $sprint->setStartDate('2026-09-01');
        $sprint->setEndDate('2026-09-14');
        $this->entityManager->persist($sprint);
        $this->entityManager->flush();

        $idASupprimer = $sprint->getId();

        $this->client->request(
            'DELETE', "/api/sprints/{$idASupprimer}",
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        // Vérifie que le sprint n'existe plus
        $sprintSupprime = $this->entityManager->getRepository(Sprint::class)->find($idASupprimer);
        $this->assertNull($sprintSupprime);
    }
}
