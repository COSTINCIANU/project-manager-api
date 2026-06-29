<?php

// =====================================================
// Sprint.php — Entité Sprint
// Un sprint est une période de travail fixe
// pendant laquelle l'équipe s'engage sur des tâches
// Statuts : planifie, actif, termine
// =====================================================

namespace App\Entity;

use App\Repository\SprintRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SprintRepository::class)]
class Sprint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Nom du sprint (ex: "Sprint 1", "Sprint Mars 2026")
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Objectif du sprint — ce qu'on veut accomplir
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $goal = null;

    // Statut : planifie, actif, termine
    #[ORM\Column(length: 20)]
    private ?string $status = null;

    // Date de début au format AAAA-MM-JJ
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $startDate = null;

    // Date de fin au format AAAA-MM-JJ
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $endDate = null;

    // Projet auquel appartient ce sprint
    #[ORM\Column]
    private ?int $projectId = null;

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

    public function getGoal(): ?string
    {
        return $this->goal;
    }

    public function setGoal(?string $goal): static
    {
        $this->goal = $goal;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(?string $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): static
    {
        $this->endDate = $endDate;

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
}
