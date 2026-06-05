<?php
// =====================================================
// ActionLogRepository.php — Repository ActionLog
// =====================================================

namespace App\Repository;

use App\Entity\ActionLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionLog::class);
    }
}
