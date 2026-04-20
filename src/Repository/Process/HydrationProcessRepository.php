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

    public function findScheduledProcess(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name = :name')
            ->andWhere('p.executedAt IS NULL AND p.scheduledAt > :now')
            ->setParameter('now', new \DateTime())
            ->setParameter('name', StartHydrationProcess::NAME)
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
