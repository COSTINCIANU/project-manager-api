<?php
// =====================================================
// CustomFieldControllerTest.php — Tests PHPUnit
// Teste les routes CustomField :
// GET par projet, GET par tâche, POST create,
// PUT update, DELETE
// =====================================================

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\CustomField;

class CustomFieldControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $token;
    private int $projetId;
    private int $tacheId;
    private int $champId;

    protected function setUp(): void
    {
        $this->client        = static::createClient();
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
        // Supprime les champs personnalisés de test
        $champs = $this->entityManager->getRepository(CustomField::class)
            ->findBy(['name' => 'Champ PHPUnit Test']);
        foreach ($champs as $c) $this->entityManager->remove($c);

        // Supprime les tâches de test
        $taches = $this->entityManager->getRepository(Task::class)
            ->findBy(['name' => 'Tâche CustomField PHPUnit']);
        foreach ($taches as $t) $this->entityManager->remove($t);

        // Supprime le projet de test
        $projet = $this->entityManager->getRepository(Project::class)
            ->findOneBy(['name' => 'Projet CustomField PHPUnit']);
        if ($projet) $this->entityManager->remove($projet);

        // Supprime l'utilisateur de test
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'test.customfield@example.com']);
        if ($user) $this->entityManager->remove($user);

        $this->entityManager->flush();
    }

    private function creerDonneesTest(): void
    {
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');

        // Crée l'utilisateur admin de test
        $user = new User();
        $user->setEmail('test.customfield@example.com');
        $user->setName('Test CustomField');
        $user->setRoles(['ROLE_USER']);
        $user->setRole('admin');
        $user->setPassword($passwordHasher->hashPassword($user, 'MotDePasseTest123!'));
        $this->entityManager->persist($user);

        // Crée le projet de test
        $projet = new Project();
        $projet->setName('Projet CustomField PHPUnit');
        $projet->setStatus('En cours');
        $projet->setColor('#378ADD');
        $projet->setProgress(0);
        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        $this->projetId = $projet->getId();

        // Crée la tâche de test
        $tache = new Task();
        $tache->setName('Tâche CustomField PHPUnit');
        $tache->setPriority('normale');
        $tache->setDone(false);
        $tache->setInProgress(false);
        $tache->setProjectId($this->projetId);
        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $this->tacheId = $tache->getId();

        // Crée un champ au niveau projet (sans tâche)
        $champProjet = new CustomField();
        $champProjet->setName('Champ PHPUnit Test');
        $champProjet->setLabel('Champ PHPUnit Test');
        $champProjet->setType('text');
        $champProjet->setValue('Valeur projet');
        $champProjet->setProjectId($this->projetId);
        $champProjet->setTaskId(null);
        $this->entityManager->persist($champProjet);

        // Crée un champ au niveau tâche
        $champTache = new CustomField();
        $champTache->setName('Champ PHPUnit Test');
        $champTache->setLabel('Champ PHPUnit Test');
        $champTache->setType('text');
        $champTache->setValue('Valeur tâche');
        $champTache->setProjectId($this->projetId);
        $champTache->setTaskId($this->tacheId);
        $this->entityManager->persist($champTache);

        $this->entityManager->flush();

        // On garde l'id du champ tâche pour les tests PUT/DELETE
        $this->champId = $champTache->getId();
            }

    private function connecter(): void
    {
        $this->client->request(
            'POST', '/api/auth/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test.customfield@example.com', 'password' => 'MotDePasseTest123!'])
        );
        $reponse     = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $reponse['token'];
    }

    // =====================
    // TEST 1 — GET champs par projet
    // =====================
    public function testGetChampsParProjet(): void
    {
        $this->client->request(
            'GET', "/api/custom-fields/project/{$this->projetId}",
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($données);
        $this->assertGreaterThanOrEqual(1, count($données));
    }

    // =====================
    // TEST 2 — GET champs par tâche
    // =====================
    public function testGetChampsParTache(): void
    {
        $this->client->request(
            'GET', "/api/custom-fields/task/{$this->tacheId}",
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($données);
        $this->assertGreaterThanOrEqual(1, count($données));
    }

    // =====================
    // TEST 3 — POST créer un champ personnalisé
    // =====================
    public function testCreerChampPersonnalise(): void
    {
        $this->client->request(
            'POST', '/api/custom-fields',
            [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode([
                'name'      => 'Champ PHPUnit Test',
                'label'     => 'Champ PHPUnit Test',
                'type'      => 'number',
                'value'     => '42',
                'projectId' => $this->projetId,
                'taskId'    => $this->tacheId,
            ])
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id',   $données);
        $this->assertArrayHasKey('name', $données);
        $this->assertEquals('Champ PHPUnit Test', $données['name']);

        // Nettoyage du champ créé
        $champ = $this->entityManager->getRepository(CustomField::class)->find($données['id']);
        if ($champ) {
            $this->entityManager->remove($champ);
            $this->entityManager->flush();
        }
    }

    // =====================
    // TEST 4 — PUT mettre à jour un champ
    // =====================
    public function testMettreAJourChamp(): void
    {
        $this->client->request(
            'PUT', "/api/custom-fields/{$this->champId}",
            [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode([
                'name'  => 'Champ PHPUnit Test',
                'value' => 'Valeur mise à jour',
            ])
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Valeur mise à jour', $données['value']);
    }

    // =====================
    // TEST 5 — DELETE supprimer un champ
    // =====================
    public function testSupprimerChamp(): void
    {
        // Crée un champ temporaire à supprimer
        $champ = new CustomField();
        $champ->setName('Champ PHPUnit Test');
        $champ->setLabel('Champ PHPUnit Test');
        $champ->setType('text');
        $champ->setValue('À supprimer');
        $champ->setProjectId($this->projetId);
        $champ->setTaskId($this->tacheId);
        $this->entityManager->persist($champ);
        $this->entityManager->flush();

        $idASupprimer = $champ->getId();

        $this->client->request(
            'DELETE', "/api/custom-fields/{$idASupprimer}",
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        // Vérifie que le champ n'existe plus
        $champSupprime = $this->entityManager->getRepository(CustomField::class)->find($idASupprimer);
        $this->assertNull($champSupprime);
    }
}
