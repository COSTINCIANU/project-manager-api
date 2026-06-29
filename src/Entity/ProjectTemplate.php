<?php

namespace App\Entity;

use App\Repository\ProjectTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTemplateRepository::class)]
class ProjectTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Nom du template (ex: "Projet web", "Marketing")
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Description courte du template
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    // Couleur hex du template (ex: #9B7FD4)
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null;

    // Icône du template (ex: "web", "mobile", "marketing")
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    // Liste des tâches préremplies liées à ce template
    #[ORM\OneToMany(targetEntity: ProjectTemplateTask::class, mappedBy: 'template', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(ProjectTemplateTask $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setTemplate($this);
        }

        return $this;
    }

    public function removeTask(ProjectTemplateTask $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getTemplate() === $this) {
                $task->setTemplate(null);
            }
        }

        return $this;
    }
}
