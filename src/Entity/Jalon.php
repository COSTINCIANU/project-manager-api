<?php
// =====================================================
// Jalon.php — Entité Jalon (Milestone)
// Un jalon est un point clé dans un projet
// avec un nom et une date cible
// Exemples : "Livraison MVP", "Recette client", "Mise en prod"
// =====================================================

namespace App\Entity;

use App\Repository\JalonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JalonRepository::class)]
#[ORM\Table(name: 'jalon')]
class Jalon
{
    // Identifiant unique du jalon
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Nom du jalon ex: "Livraison MVP", "Recette client"
    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    // Description optionnelle du jalon
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Date cible du jalon au format Y-m-d
    #[ORM\Column(length: 20)]
    private ?string $date = null;

    // Projet auquel appartient le jalon
    #[ORM\Column]
    private ?int $projetId = null;

    // Jalon atteint ou non
    #[ORM\Column]
    private bool $atteint = false;

    // Couleur du jalon sur la timeline
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleur = null;

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getDate(): ?string { return $this->date; }
    public function setDate(string $date): static { $this->date = $date; return $this; }

    public function getProjetId(): ?int { return $this->projetId; }
    public function setProjetId(int $projetId): static { $this->projetId = $projetId; return $this; }

    public function isAtteint(): bool { return $this->atteint; }
    public function setAtteint(bool $atteint): static { $this->atteint = $atteint; return $this; }

    public function getCouleur(): ?string { return $this->couleur; }
    public function setCouleur(?string $couleur): static { $this->couleur = $couleur; return $this; }

    // Convertit le jalon en tableau pour l'API
    public function versTableau(): array
    {
        return [
            'id'          => $this->id,
            'nom'         => $this->nom,
            'description' => $this->description,
            'date'        => $this->date,
            'projetId'    => $this->projetId,
            'atteint'     => $this->atteint,
            'couleur'     => $this->couleur ?? '#378ADD',
        ];
    }
}
