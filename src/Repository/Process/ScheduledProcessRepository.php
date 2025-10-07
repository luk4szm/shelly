<?php

namespace App\Repository\Process;

use App\Entity\Process\ScheduledProcess;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<ScheduledProcess>
 */
class ScheduledProcessRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledProcess::class);
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
}
