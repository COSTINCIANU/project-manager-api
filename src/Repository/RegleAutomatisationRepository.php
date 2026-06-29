<?php

// =====================================================
// RegleAutomatisationRepository.php — Recherches en BDD
// Fournit les méthodes pour retrouver les règles
// =====================================================

namespace App\Repository;

use App\Entity\RegleAutomatisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RegleAutomatisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegleAutomatisation::class);
    }

    // Récupère toutes les règles actives d'un projet
    public function trouverActivesParProjet(int $projetId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.projet = :projetId')
            ->andWhere('r.active = true')
            ->setParameter('projetId', $projetId)
            ->orderBy('r.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Récupère les règles actives d'un projet filtrées par déclencheur
    public function trouverParDeclencheur(int $projetId, string $declencheur): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.projet = :projetId')
            ->andWhere('r.declencheur = :declencheur')
            ->andWhere('r.active = true')
            ->setParameter('projetId', $projetId)
            ->setParameter('declencheur', $declencheur)
            ->getQuery()
            ->getResult();
    }
}
