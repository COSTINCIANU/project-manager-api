<?php

// =====================================================
// PushSubscription.php — Entité abonnement push
// Stocke les abonnements aux notifications push
// de chaque navigateur/appareil de l'utilisateur
// =====================================================

namespace App\Entity;

use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Email de l'utilisateur abonné
    #[ORM\Column(length: 180)]
    private ?string $userEmail = null;

    // Endpoint unique du navigateur
    #[ORM\Column(type: 'text')]
    private ?string $endpoint = null;

    // Clé publique p256dh
    #[ORM\Column(type: 'text')]
    private ?string $p256dh = null;

    // Clé d'authentification
    #[ORM\Column(type: 'text')]
    private ?string $auth = null;

    // Date d'inscription
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $email): static
    {
        $this->userEmail = $email;

        return $this;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function getP256dh(): ?string
    {
        return $this->p256dh;
    }

    public function setP256dh(string $p256dh): static
    {
        $this->p256dh = $p256dh;

        return $this;
    }

    public function getAuth(): ?string
    {
        return $this->auth;
    }

    public function setAuth(string $auth): static
    {
        $this->auth = $auth;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
