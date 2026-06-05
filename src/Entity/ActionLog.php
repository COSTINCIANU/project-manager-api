<?php
// =====================================================
// ActionLog.php — Entité historique des actions
// Enregistre chaque action importante de l'application
// avec l'utilisateur, le type d'action et la date
// =====================================================

namespace App\Entity;

use App\Repository\ActionLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActionLogRepository::class)]
class ActionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Type d'action : create_task, update_task, delete_task, etc.
    #[ORM\Column(length: 50)]
    private ?string $action = null;

    // Description lisible de l'action
    #[ORM\Column(length: 255)]
    private ?string $description = null;

    // Email de l'utilisateur qui a effectué l'action
    #[ORM\Column(length: 180)]
    private ?string $userEmail = null;

    // Type d'entité concernée : task, project, user
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $entityType = null;

    // ID de l'entité concernée
    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    // Date de l'action
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getAction(): ?string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getUserEmail(): ?string { return $this->userEmail; }
    public function setUserEmail(string $userEmail): static { $this->userEmail = $userEmail; return $this; }

    public function getEntityType(): ?string { return $this->entityType; }
    public function setEntityType(?string $entityType): static { $this->entityType = $entityType; return $this; }

    public function getEntityId(): ?int { return $this->entityId; }
    public function setEntityId(?int $entityId): static { $this->entityId = $entityId; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
