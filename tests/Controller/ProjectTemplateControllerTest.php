<?php

// =====================================================
// ProjectTemplateControllerTest.php — Tests PHPUnit
// Teste les routes Templates :
// GET liste, GET show, POST create,
// POST create-project, DELETE
// =====================================================

namespace App\Tests\Controller;

use App\Entity\ProjectTemplate;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProjectTemplateControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $token;
    private int $templateId;

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
        $templates = $this->entityManager->getRepository(ProjectTemplate::class)
            ->findBy(['name' => 'Template PHPUnit Test']);
        foreach ($templates as $t) {
            $this->entityManager->remove($t);
        }

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'test.template@example.com']);
        if ($user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
    }

    private function creerDonneesTest(): void
    {
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');

        // Crée un utilisateur admin
        $user = new User();
        $user->setEmail('test.template@example.com');
        $user->setName('Test Template');
        $user->setRoles(['ROLE_USER']);
        $user->setRole('admin');
        $user->setPassword($passwordHasher->hashPassword($user, 'MotDePasseTest123!'));
        $this->entityManager->persist($user);

        // Dans creerDonneesTest() — remplace
        $template = new ProjectTemplate();
        $template->setName('Template PHPUnit Test');
        $template->setDescription('Description test PHPUnit');
        $template->setColor('#639922');
        $template->setIcon('📋');
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $this->templateId = $template->getId();
    }

    private function connecter(): void
    {
        $this->client->request(
            'POST', '/api/auth/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test.template@example.com', 'password' => 'MotDePasseTest123!'])
        );
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $reponse['token'];
    }

    // =====================
    // TEST 1 — GET liste des templates
    // =====================
    public function testGetListeTemplates(): void
    {
        $this->client->request(
            'GET', '/api/templates',
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($données);
        $this->assertGreaterThanOrEqual(1, count($données));
    }

    // =====================
    // TEST 2 — GET un template par ID
    // =====================
    public function testGetTemplateParId(): void
    {
        $this->client->request(
            'GET', "/api/templates/{$this->templateId}",
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $données);
        $this->assertArrayHasKey('name', $données);
        $this->assertEquals('Template PHPUnit Test', $données['name']);
    }

    // =====================
    // TEST 3 — POST créer un template
    // =====================
    public function testCreerTemplate(): void
    {
        $this->client->request(
            'POST', '/api/templates',
            [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            // Dans testCreerTemplate() — remplace le json_encode
            json_encode([
                'name' => 'Template PHPUnit Test',
                'description' => 'Créé par PHPUnit',
                'color' => '#378ADD',
                'icon' => '📋',
            ])
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $données);
        $this->assertEquals('Template PHPUnit Test', $données['name']);

        // Nettoyage
        $template = $this->entityManager->getRepository(ProjectTemplate::class)->find($données['id']);
        if ($template) {
            $this->entityManager->remove($template);
            $this->entityManager->flush();
        }
    }

    // =====================
    // TEST 4 — POST créer un projet depuis un template
    // =====================
    public function testCreerProjetDepuisTemplate(): void
    {
        $this->client->request(
            'POST', "/api/templates/{$this->templateId}/create-project",
            [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode(['name' => 'Projet depuis Template PHPUnit'])
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('projectId', $données);
        $this->assertArrayHasKey('projectName', $données);
    }

    // =====================
    // TEST 5 — DELETE supprimer un template
    // =====================
    public function testSupprimerTemplate(): void
    {
        // Dans testSupprimerTemplate() — remplace
        $template = new ProjectTemplate();
        $template->setName('Template PHPUnit Test');
        $template->setDescription('À supprimer');
        $template->setColor('#D85A30');
        $template->setIcon('🗑️');
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $idASupprimer = $template->getId();

        $this->client->request(
            'DELETE', "/api/templates/{$idASupprimer}",
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();

        $templateSupprime = $this->entityManager->getRepository(ProjectTemplate::class)->find($idASupprimer);
        $this->assertNull($templateSupprime);
    }

    // =====================
    // TEST 6 — GET template inexistant retourne 404
    // =====================
    public function testTemplateInexistantRetourne404(): void
    {
        $this->client->request(
            'GET', '/api/templates/99999',
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
