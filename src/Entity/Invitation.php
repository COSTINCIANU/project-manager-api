<?php

// =====================================================
// Invitation.php — Entité pour les invitations
// Stocke les invitations envoyées aux utilisateurs
// pour rejoindre un projet
// =====================================================

namespace App\Entity;

use App\Repository\InvitationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvitationRepository::class)]
class Invitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Email de la personne invitée
    #[ORM\Column(length: 255)]
    private ?string $email = null;

    // Id du projet auquel on invite
    #[ORM\Column]
    private ?int $projectId = null;

    // Token unique pour accepter l'invitation
    #[ORM\Column(length: 255, unique: true)]
    private ?string $token = null;

    // Statut : pending, accepted, rejected
    #[ORM\Column(length: 20)]
    private ?string $status = 'pending';

    // Date de création
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        // Génère un token unique aléatoire
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function setProjectId(int $projectId): static
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
