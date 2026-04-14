<?php

namespace App\Repository\Process;

use App\Entity\Process\HydrationProcess;
use App\Repository\Abstraction\CrudRepository;
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
}
