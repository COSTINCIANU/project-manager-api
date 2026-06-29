<?php

// =====================================================
// RegleAutomatisation.php — Entité Règle d'automatisation
// Stocke une règle "Quand X → faire Y" liée à un projet
// Exemple : "Quand une tâche passe à Terminé → notifier le manager"
// =====================================================

namespace App\Entity;

use App\Repository\RegleAutomatisationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegleAutomatisationRepository::class)]
#[ORM\Table(name: 'regle_automatisation')]
class RegleAutomatisation
{
    // Identifiant unique de la règle
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Projet auquel appartient cette règle
    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $projet = null;

    // Déclencheur — ce qui provoque l'exécution de la règle
    // Valeurs possibles :
    //   tache_statut_change  → une tâche change de statut
    //   tache_creee          → une nouvelle tâche est créée
    //   tache_assignee       → une tâche est assignée à quelqu'un
    //   tache_en_retard      → une tâche dépasse sa date d'échéance
    #[ORM\Column(type: 'string', length: 100)]
    private string $declencheur = '';

    // Valeur du déclencheur
    // Exemple : si declencheur=tache_statut_change, valeurDeclencheur=Terminé
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $valeurDeclencheur = null;

    // Action à exécuter quand la règle se déclenche
    // Valeurs possibles :
    //   notifier_manager     → envoie une notification au manager du projet
    //   changer_priorite     → change la priorité de la tâche
    //   envoyer_email        → envoie un email à l'adresse définie dans valeurAction
    #[ORM\Column(type: 'string', length: 100)]
    private string $action = '';

    // Valeur de l'action
    // Exemple : si action=changer_priorite, valeurAction=haute
    // Exemple : si action=envoyer_email, valeurAction=manager@example.com
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $valeurAction = null;

    // Nom lisible de la règle affiché dans l'interface
    // Exemple : "Passer en haute priorité quand tâche terminée"
    #[ORM\Column(type: 'string', length: 255)]
    private string $nom = '';

    // Règle active ou désactivée — false = la règle ne se déclenche pas
    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    // Date de création de la règle
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $creeLe;

    // Constructeur — initialise la date de création automatiquement
    public function __construct()
    {
        $this->creeLe = new \DateTime();
    }

    // =====================
    // GETTERS ET SETTERS
    // =====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjet(): ?Project
    {
        return $this->projet;
    }

    public function setProjet(?Project $projet): static
    {
        $this->projet = $projet;

        return $this;
    }

    public function getDeclencheur(): string
    {
        return $this->declencheur;
    }

    public function setDeclencheur(string $declencheur): static
    {
        $this->declencheur = $declencheur;

        return $this;
    }

    public function getValeurDeclencheur(): ?string
    {
        return $this->valeurDeclencheur;
    }

    public function setValeurDeclencheur(?string $valeurDeclencheur): static
    {
        $this->valeurDeclencheur = $valeurDeclencheur;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getValeurAction(): ?string
    {
        return $this->valeurAction;
    }

    public function setValeurAction(?string $valeurAction): static
    {
        $this->valeurAction = $valeurAction;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getCreeLe(): \DateTimeInterface
    {
        return $this->creeLe;
    }

    // Sérialisation pour les réponses JSON envoyées au web et au mobile
    public function versTableau(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'declencheur' => $this->declencheur,
            'valeurDeclencheur' => $this->valeurDeclencheur,
            'action' => $this->action,
            'valeurAction' => $this->valeurAction,
            'active' => $this->active,
            'projetId' => $this->projet?->getId(),
            'creeLe' => $this->creeLe->format('Y-m-d H:i:s'),
        ];
    }
}
