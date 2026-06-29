<?php

// =====================================================
// ChatMessage.php — Entité message de chat
// Stocke les messages de l'équipe
//
// MIGRATION WEBSOCKET FUTURE :
// Remplacer le polling React par WebSocket
// Côté Symfony : installer Mercure ou Ratchet
// composer require symfony/mercure-bundle
// =====================================================

namespace App\Entity;

use App\Repository\ChatMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Contenu du message
    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    // Email de l'expéditeur
    #[ORM\Column(length: 180)]
    private ?string $senderEmail = null;

    // Nom affiché de l'expéditeur
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $senderName = null;

    // Date d'envoi
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(string $email): static
    {
        $this->senderEmail = $email;

        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $name): static
    {
        $this->senderName = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
