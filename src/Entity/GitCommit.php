<?php
// =====================================================
// GitCommit.php — Entité pour les commits GitHub
// Stocke les commits GitHub liés aux tâches
// =====================================================

namespace App\Entity;

use App\Repository\GitCommitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GitCommitRepository::class)]
class GitCommit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // SHA du commit GitHub (identifiant unique)
    #[ORM\Column(length: 255)]
    private ?string $sha = null;

    // Message du commit
    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    // Auteur du commit
    #[ORM\Column(length: 255)]
    private ?string $author = null;

    // URL du commit sur GitHub
    #[ORM\Column(length: 500)]
    private ?string $url = null;

    // Id de la tâche liée (extrait du message du commit)
    // Convention : "#123" dans le message lie au task id 123
    #[ORM\Column(nullable: true)]
    private ?int $taskId = null;

    // Nom du repository
    #[ORM\Column(length: 255)]
    private ?string $repository = null;

    // Date du commit
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $committedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getSha(): ?string { return $this->sha; }
    public function setSha(string $sha): static { $this->sha = $sha; return $this; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(string $author): static { $this->author = $author; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(string $url): static { $this->url = $url; return $this; }

    public function getTaskId(): ?int { return $this->taskId; }
    public function setTaskId(?int $taskId): static { $this->taskId = $taskId; return $this; }

    public function getRepository(): ?string { return $this->repository; }
    public function setRepository(string $repository): static { $this->repository = $repository; return $this; }

    public function getCommittedAt(): ?\DateTimeInterface { return $this->committedAt; }
    public function setCommittedAt(\DateTimeInterface $committedAt): static { $this->committedAt = $committedAt; return $this; }
}
