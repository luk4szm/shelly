<?php

namespace App\Repository\Process;

use App\Entity\Process\HydrationProcess;
use App\Repository\Abstraction\CrudRepository;
use App\Service\Processable\StartHydrationProcess;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<HydrationProcess>
 */
class HydrationProcessRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HydrationProcess::class);
    }

    public function findProcessToExecute(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.scheduledAt <= :now')
            ->andWhere('p.executedAt IS NULL')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds processes that are either waiting to be executed or are currently running
     * based on their scheduled start time and duration.
     */
    public function findPendingAndActiveProcesses(): array
    {
        $now    = new \DateTime();
        $buffer = (clone $now)->modify('-5 seconds');

        return $this->createQueryBuilder('p')
            ->where('p.name = :name')
            ->andWhere('
                (p.executedAt IS NULL AND p.scheduledAt >= :buffer)
                OR
                (p.executedAt IS NOT NULL AND DATE_ADD(p.executedAt, p.duration, \'SECOND\') > :now)
            ')
            ->setParameter('now', $now)
            ->setParameter('buffer', $buffer)
            ->setParameter('name', StartHydrationProcess::NAME)
            ->orderBy('p.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByValveAndStartMinute(string $valve, \DateTimeInterface $startsAt): ?HydrationProcess
    {
        $start = (clone $startsAt)->format('Y-m-d H:i:00');
        $end   = (clone $startsAt)->format('Y-m-d H:i:59');

        return $this->createQueryBuilder('p')
            ->where('p.valve = :valve')
            ->andWhere('p.scheduledAt BETWEEN :start AND :end')
            ->setParameter('valve', $valve)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
