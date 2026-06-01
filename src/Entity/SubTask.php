<?php
namespace App\Entity;

use App\Repository\SubTaskRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubTaskRepository::class)]
class SubTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $done = false;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'subTasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Task $task = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function isDone(): ?bool { return $this->done; }
    public function setDone(bool $done): static { $this->done = $done; return $this; }

    public function getTask(): ?Task { return $this->task; }
    public function setTask(?Task $task): static { $this->task = $task; return $this; }
}
