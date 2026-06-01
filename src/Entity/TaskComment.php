<?php
namespace App\Entity;

use App\Repository\TaskCommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskCommentRepository::class)]
class TaskComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Task $task = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): static { $this->userId = $userId; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getTask(): ?Task { return $this->task; }
    public function setTask(?Task $task): static { $this->task = $task; return $this; }
}
