<?php
// =====================================================
// GitCommitRepository.php — Repository des commits
// =====================================================

namespace App\Repository;

use App\Entity\GitCommit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GitCommitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GitCommit::class);
    }

    // Récupère les commits liés à une tâche
    public function findByTaskId(int $taskId): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.taskId = :taskId')
            ->setParameter('taskId', $taskId)
            ->orderBy('g.committedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
