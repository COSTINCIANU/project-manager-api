<?php

// =====================================================
// ApiKey.php — Entité clé API publique
// Permet aux développeurs externes d'accéder
// aux données via une clé API sécurisée
// =====================================================

namespace App\Entity;

use App\Repository\ApiKeyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\Table(name: 'api_key')]
class ApiKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Email de l'utilisateur propriétaire de la clé
    #[ORM\Column(length: 180)]
    private ?string $userEmail = null;

    // Clé API unique (64 caractères hex)
    #[ORM\Column(length: 64, unique: true)]
    private ?string $apiKey = null;

    // Nom descriptif de la clé
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Clé active ou non
    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    // Date de création
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    // Dernière utilisation
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        // Génère une clé API unique à la création
        $this->apiKey = bin2hex(random_bytes(32));
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

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }
}
