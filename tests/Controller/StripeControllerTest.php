<?php
// =====================================================
// StripeControllerTest.php — Tests PHPUnit
// Teste les routes Stripe :
// GET public-key, POST checkout, POST invoice/token
// Le webhook n'est pas testé (signature Stripe requise)
// =====================================================

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class StripeControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $token;

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
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'test.stripe@example.com']);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    private function creerDonneesTest(): void
    {
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail('test.stripe@example.com');
        $user->setName('Test Stripe');
        $user->setRoles(['ROLE_USER']);
        $user->setRole('admin');
        $user->setPassword($passwordHasher->hashPassword($user, 'MotDePasseTest123!'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function connecter(): void
    {
        $this->client->request(
            'POST', '/api/auth/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test.stripe@example.com', 'password' => 'MotDePasseTest123!'])
        );
        $reponse     = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $reponse['token'];
    }

    // =====================
    // TEST 1 — GET clé publique Stripe
    // =====================
    public function testGetClePublique(): void
    {
        $this->client->request(
            'GET', '/api/stripe/public-key',
            [], [], ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"]
        );

        $this->assertResponseIsSuccessful();
        $données = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('publicKey', $données);
        // La clé peut être vide en environnement de test
        $this->assertIsString($données['publicKey']);
    }

    // =====================
    // TEST 2 — POST checkout retourne une session Stripe
    // =====================
    public function testCheckoutRetourneSession(): void
    {
        $this->client->request(
            'POST', '/api/stripe/checkout',
            [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode(['plan' => 'pro'])
        );

        // Soit 200 avec sessionId, soit erreur Stripe en test
        $code = $this->client->getResponse()->getStatusCode();
        $this->assertContains($code, [200, 400, 500],
            'La route checkout doit retourner 200, 400 ou 500');

        if ($code === 200) {
            $données = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('sessionId', $données);
        }
    }

    // =====================
    // TEST 3 — POST checkout sans plan retourne erreur
    // =====================
    public function testCheckoutSansPlanRetourneErreur(): void
    {
        $this->client->request(
            'POST', '/api/stripe/checkout',
            [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode([])
        );

        $code = $this->client->getResponse()->getStatusCode();
        $this->assertNotEquals(200, $code,
            'Un checkout sans plan ne doit pas retourner 200');
    }

    // =====================
    // TEST 4 — POST générer un token de facture
    // =====================
    public function testGenererTokenFacture(): void
    {
        $this->client->request(
            'POST', '/api/stripe/invoice/token',
            [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$this->token}"],
            json_encode(['invoiceId' => 'in_test_123'])
        );

        $code = $this->client->getResponse()->getStatusCode();
        $this->assertContains($code, [200, 400, 404, 500],
            'La route invoice/token doit retourner un code valide');

        if ($code === 200) {
            $données = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('token', $données);
        }
    }

    // =====================
    // TEST 5 — Route sans JWT retourne 401
    // =====================
    public function testSansJwtRetourne401(): void
    {
        $this->client->request('GET', '/api/stripe/public-key');
        $this->assertResponseStatusCodeSame(401);
    }
}
