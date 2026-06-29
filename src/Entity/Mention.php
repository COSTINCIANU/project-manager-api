<?php

// =====================================================
// Mention.php — Entité mention
// Enregistre les mentions @utilisateur dans les commentaires
// Permet d'envoyer des notifications par email
// =====================================================

namespace App\Entity;

use App\Repository\MentionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MentionRepository::class)]
class Mention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Email de l'utilisateur mentionné
    #[ORM\Column(length: 180)]
    private ?string $mentionedEmail = null;

    // Email de l'utilisateur qui a fait la mention
    #[ORM\Column(length: 180)]
    private ?string $mentionedByEmail = null;

    // ID du commentaire où la mention a été faite
    #[ORM\Column]
    private ?int $commentId = null;

    // ID de la tâche concernée
    #[ORM\Column]
    private ?int $taskId = null;

    // Mention lue ou non
    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    // Date de la mention
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

    public function getMentionedEmail(): ?string
    {
        return $this->mentionedEmail;
    }

    public function setMentionedEmail(string $email): static
    {
        $this->mentionedEmail = $email;

        return $this;
    }

    public function getMentionedByEmail(): ?string
    {
        return $this->mentionedByEmail;
    }

    public function setMentionedByEmail(string $email): static
    {
        $this->mentionedByEmail = $email;

        return $this;
    }

    public function getCommentId(): ?int
    {
        return $this->commentId;
    }

    public function setCommentId(int $commentId): static
    {
        $this->commentId = $commentId;

        return $this;
    }

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): static
    {
        $this->taskId = $taskId;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
