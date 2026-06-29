<?php

namespace App\Entity;

use App\Repository\ProjectTemplateTaskRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTemplateTaskRepository::class)]
class ProjectTemplateTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Nom de la tâche préremplie
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Priorité de la tâche (normale, haute, critique, basse)
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $priority = null;

    // Ordre d'affichage de la tâche dans le template
    #[ORM\Column]
    private ?int $position = null;

    // Template auquel appartient cette tâche
    #[ORM\ManyToOne(targetEntity: ProjectTemplate::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProjectTemplate $template = null;

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

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTemplate(): ?ProjectTemplate
    {
        return $this->template;
    }

    public function setTemplate(?ProjectTemplate $template): static
    {
        $this->template = $template;

        return $this;
    }
}
