<?php
// =====================================================
// ApiKeyRepository.php — Repository ApiKey
// =====================================================

namespace App\Repository;

use App\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    // Trouve une clé API active par sa valeur
    public function findActiveKey(string $apiKey): ?ApiKey
    {
        return $this->findOneBy([
            'apiKey' => $apiKey,
            'isActive' => true,
        ]);
    }
}
