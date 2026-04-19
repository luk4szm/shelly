<?php

namespace App\Repository;

use App\Entity\HydrationLog;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<HydrationLog>
 */
class HydrationLogRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HydrationLog::class);
    }

    public function findActiveLog(string $valve): ?HydrationLog
    {
        return $this->createQueryBuilder('hl')
            ->where('hl.valve = :valve')
            ->andWhere('hl.endsAt IS NULL')
            ->setParameter('valve', $valve)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveLogs(): array
    {
        return $this->createQueryBuilder('hl')
            ->andWhere('hl.endsAt IS NULL')
            ->getQuery()
            ->getResult();
    }
}
