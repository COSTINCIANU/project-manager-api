<?php

// =====================================================
// WikiPage.php — Entité page Wiki
// Chaque projet peut avoir plusieurs pages Wiki
// avec du contenu Markdown
// =====================================================

namespace App\Entity;

use App\Repository\WikiPageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WikiPageRepository::class)]
class WikiPage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Titre de la page Wiki
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    // Contenu en Markdown
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    // ID du projet associé
    #[ORM\Column]
    private ?int $projectId = null;

    // Email de l'auteur
    #[ORM\Column(length: 180)]
    private ?string $authorEmail = null;

    // Date de création
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    // Date de dernière modification
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

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

    public function getAuthorEmail(): ?string
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail(string $email): static
    {
        $this->authorEmail = $email;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
