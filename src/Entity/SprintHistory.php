<?php
// =====================================================
// SprintHistory.php — Historique journalier d'un sprint
// Enregistre chaque jour l'état du sprint pour
// générer le Burndown chart
// Un cron job tourne chaque nuit à minuit pour
// sauvegarder les données
// =====================================================

namespace App\Entity;

use App\Repository\SprintHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SprintHistoryRepository::class)]
class SprintHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Sprint concerné
    #[ORM\Column]
    private ?int $sprintId = null;

    // Date de l'enregistrement
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    // Nombre total de tâches dans le sprint ce jour-là
    #[ORM\Column]
    private ?int $tasksTotal = null;

    // Nombre de tâches pas encore terminées
    #[ORM\Column]
    private ?int $tasksRemaining = null;

    // Nombre de tâches terminées
    #[ORM\Column]
    private ?int $tasksDone = null;

    public function getId(): ?int { return $this->id; }

    public function getSprintId(): ?int { return $this->sprintId; }
    public function setSprintId(int $sprintId): static { $this->sprintId = $sprintId; return $this; }

    public function getDate(): ?\DateTime { return $this->date; }
    public function setDate(\DateTime $date): static { $this->date = $date; return $this; }

    public function getTasksTotal(): ?int { return $this->tasksTotal; }
    public function setTasksTotal(int $tasksTotal): static { $this->tasksTotal = $tasksTotal; return $this; }

    public function getTasksRemaining(): ?int { return $this->tasksRemaining; }
    public function setTasksRemaining(int $tasksRemaining): static { $this->tasksRemaining = $tasksRemaining; return $this; }

    public function getTasksDone(): ?int { return $this->tasksDone; }
    public function setTasksDone(int $tasksDone): static { $this->tasksDone = $tasksDone; return $this; }
}
