<?php
namespace App\Entity;

use App\Entity\TaskComment;
use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $priority = null;

    // Type du ticket : task, bug, story, epic
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $ticketType = null;

    #[ORM\Column]
    private ?bool $done = null;

    #[ORM\Column]
    private ?bool $inProgress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $dueDate = null;

    #[ORM\Column]
    private ?int $projectId = null;

    #[ORM\Column(nullable: true)]
    private ?int $estimatedTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $elapsedTime = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = null;

    #[ORM\Column(nullable: true)]
    private ?int $assignedTo = null;

    // ID de la tâche dont celle-ci dépend (bloquante)
    #[ORM\Column(nullable: true)]
    private ?int $dependsOn = null;

    // Sprint auquel est assignée cette tâche (null = backlog)
    #[ORM\Column(nullable: true)]
    private ?int $sprintId = null;

    // Lien externe — Google Drive, Dropbox, etc.
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $externalLink = null;


    #[ORM\OneToMany(mappedBy: 'task', targetEntity: SubTask::class, cascade: ['persist', 'remove'])]
    private Collection $subTasks;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskComment::class, cascade: ['persist', 'remove'])]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: Attachment::class, cascade: ['persist', 'remove'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->subTasks = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getPriority(): ?string { return $this->priority; }
    public function setPriority(?string $priority): static { $this->priority = $priority; return $this; }

    public function getTicketType(): ?string { return $this->ticketType; }
    public function setTicketType(?string $ticketType): static { $this->ticketType = $ticketType; return $this; }

    public function isDone(): ?bool { return $this->done; }
    public function setDone(bool $done): static { $this->done = $done; return $this; }

    public function isInProgress(): ?bool { return $this->inProgress; }
    public function setInProgress(bool $inProgress): static { $this->inProgress = $inProgress; return $this; }

    public function getDueDate(): ?string { return $this->dueDate; }
    public function setDueDate(?string $dueDate): static { $this->dueDate = $dueDate; return $this; }

    public function getProjectId(): ?int { return $this->projectId; }
    public function setProjectId(int $projectId): static { $this->projectId = $projectId; return $this; }

    public function getEstimatedTime(): ?int { return $this->estimatedTime; }
    public function setEstimatedTime(?int $estimatedTime): static { $this->estimatedTime = $estimatedTime; return $this; }

    public function getElapsedTime(): ?int { return $this->elapsedTime; }
    public function setElapsedTime(?int $elapsedTime): static { $this->elapsedTime = $elapsedTime; return $this; }

    public function getTags(): ?array { return $this->tags; }
    public function setTags(?array $tags): static { $this->tags = $tags; return $this; }

    public function getAssignedTo(): ?int { return $this->assignedTo; }
    public function setAssignedTo(?int $assignedTo): static { $this->assignedTo = $assignedTo; return $this; }

    public function getDependsOn(): ?int { return $this->dependsOn; }
    public function setDependsOn(?int $dependsOn): static { $this->dependsOn = $dependsOn; return $this; }

    public function getSprintId(): ?int { return $this->sprintId; }
    public function setSprintId(?int $sprintId): static { $this->sprintId = $sprintId; return $this; }

    public function getExternalLink(): ?string { return $this->externalLink; }
    public function setExternalLink(?string $externalLink): static { $this->externalLink = $externalLink; return $this; }


    public function getSubTasks(): Collection { return $this->subTasks; }
    public function getComments(): Collection { return $this->comments; }
    public function getAttachments(): Collection { return $this->attachments; }
}
