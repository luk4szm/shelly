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

    public function findPreviousRuns(\DateTimeInterface $dateTime = null): array
    {
        if ($dateTime) {
            $start = (clone $dateTime)->setTime(0, 0);
            $end   = (clone $dateTime)->setTime(23, 59, 59);
        } else {
            $end   = new \DateTime();
            $start = (clone $end)->modify('-24 hours');
        }

        return $this->createQueryBuilder('hl')
            ->where('hl.endsAt IS NOT NULL')
            ->andWhere('hl.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}
