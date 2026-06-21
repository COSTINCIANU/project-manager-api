<?php
// =====================================================
// CustomField.php — Champ personnalisé
// Permet d'ajouter des champs sur mesure aux projets et tâches
// Types disponibles : text, number, date, boolean
// Exemple : "Budget", "Client", "Approuvé", "Date de livraison"
// =====================================================

namespace App\Entity;

use App\Repository\CustomFieldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomFieldRepository::class)]
class CustomField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Nom technique du champ (ex: "budget", "client_name")
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Label affiché à l'utilisateur (ex: "Budget", "Nom du client")
    #[ORM\Column(length: 255)]
    private ?string $label = null;

    // Type du champ : text, number, date, boolean
    #[ORM\Column(length: 50)]
    private ?string $type = null;

    // Valeur saisie par l'utilisateur — stockée en texte pour tous les types
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    // Projet auquel appartient ce champ
    #[ORM\Column]
    private ?int $projectId = null;

    // Tâche à laquelle appartient ce champ (optionnel)
    #[ORM\Column(nullable: true)]
    private ?int $taskId = null;

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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;
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

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function setTaskId(?int $taskId): static
    {
        $this->taskId = $taskId;
        return $this;
    }
}
