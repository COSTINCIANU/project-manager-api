<?php
// =====================================================
// JalonRepository.php — Repository des jalons
// Méthodes de recherche pour l'entité Jalon
// =====================================================

namespace App\Repository;

use App\Entity\Jalon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JalonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Jalon::class);
    }

    // Récupère tous les jalons d'un projet triés par date
    public function findByProjetOrdonnes(int $projetId): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.projetId = :projetId')
            ->setParameter('projetId', $projetId)
            ->orderBy('j.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
