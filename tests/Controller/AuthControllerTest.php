<?php
// =====================================================
// AuthControllerTest.php — Tests de l'authentification
// Teste les routes : inscription, connexion, refresh token
// =====================================================

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class AuthControllerTest extends WebTestCase
{
    // private $client;
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    // S'exécute avant chaque test — prépare le client HTTP et la BDD
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->nettoyerUtilisateurDeTest();
    }

    // S'exécute après chaque test — nettoie la BDD
    protected function tearDown(): void
    {
        $this->nettoyerUtilisateurDeTest();
        parent::tearDown();
    }

    // Supprime l'utilisateur de test s'il existe déjà en BDD
    private function nettoyerUtilisateurDeTest(): void
    {
        $utilisateur = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'test.phpunit@example.com']);

        if ($utilisateur) {
            $this->entityManager->remove($utilisateur);
            $this->entityManager->flush();
        }
    }

    // =====================================================
    // TESTS INSCRIPTION
    // =====================================================

    // Vérifie qu'on peut créer un nouveau compte avec un email et mot de passe valides
    public function testInscriptionReussie(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'MotDePasse123!',
                'name' => 'Utilisateur Test',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $reponse);
        $this->assertEquals('Inscription réussie !', $reponse['message']);
    }

    // Vérifie qu'on ne peut pas créer deux comptes avec le même email
    public function testInscriptionEmailDejaUtilise(): void
    {
        // On crée un premier compte
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'MotDePasse123!',
            ])
        );

        // On essaie de créer un deuxième compte avec le même email
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'AutreMotDePasse456!',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $reponse);
    }

    // Vérifie qu'on ne peut pas s'inscrire sans email ni mot de passe
    public function testInscriptionSansEmailNiMotDePasse(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    // =====================================================
    // TESTS CONNEXION
    // =====================================================

    // Vérifie qu'on peut se connecter avec un email et mot de passe corrects
    public function testConnexionReussie(): void
    {
        // On crée d'abord un compte
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'MotDePasse123!',
            ])
        );

        // On essaie de se connecter avec ce compte
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'MotDePasse123!',
            ])
        );

        $this->assertResponseIsSuccessful();
        $reponse = json_decode($this->client->getResponse()->getContent(), true);

        // Vérifie que la réponse contient bien un token JWT et un refresh token
        $this->assertArrayHasKey('token', $reponse);
        $this->assertArrayHasKey('refreshToken', $reponse);
        $this->assertNotEmpty($reponse['token']);
        $this->assertNotEmpty($reponse['refreshToken']);
    }

    // Vérifie qu'on ne peut pas se connecter avec un mauvais mot de passe
    public function testConnexionMauvaisMotDePasse(): void
    {
        // On crée d'abord un compte
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'MotDePasse123!',
            ])
        );

        // On essaie de se connecter avec un mauvais mot de passe
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'MauvaisMotDePasse!',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $reponse);
    }

    // Vérifie qu'on ne peut pas se connecter avec un email qui n'existe pas
    public function testConnexionEmailInexistant(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'email.qui.nexiste.pas@example.com',
                'password' => 'MotDePasse123!',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    // =====================================================
    // TESTS REFRESH TOKEN
    // =====================================================

    // Vérifie qu'on peut obtenir un nouveau JWT avec un refresh token valide
    public function testRefreshTokenValide(): void
    {
        // On crée un compte et on se connecte pour récupérer le refresh token
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'MotDePasse123!',
            ])
        );

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test.phpunit@example.com',
                'password' => 'MotDePasse123!',
            ])
        );

        $reponseLogin = json_decode($this->client->getResponse()->getContent(), true);
        $refreshToken = $reponseLogin['refreshToken'];

        // On utilise le refresh token pour obtenir un nouveau JWT
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => $refreshToken,
            ])
        );

        $this->assertResponseIsSuccessful();
        $reponse = json_decode($this->client->getResponse()->getContent(), true);

        // Vérifie que la réponse contient un nouveau JWT et un nouveau refresh token
        $this->assertArrayHasKey('token', $reponse);
        $this->assertArrayHasKey('refreshToken', $reponse);
        $this->assertNotEmpty($reponse['token']);
        $this->assertNotEmpty($reponse['refreshToken']);
    }

    // Vérifie qu'un refresh token inventé est bien refusé
    public function testRefreshTokenInvalide(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refreshToken' => 'token-completement-faux-qui-nexiste-pas',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    // Vérifie qu'on ne peut pas appeler /refresh sans envoyer de token
    public function testRefreshTokenManquant(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);
    }
}
