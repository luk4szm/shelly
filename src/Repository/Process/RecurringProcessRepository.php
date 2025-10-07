<?php

namespace App\Repository\Process;

use App\Entity\Process\RecurringProcess;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<RecurringProcess>
 */
class RecurringProcessRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringProcess::class);
    }

    public function findProcessToExecute(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :is_active')
            ->setParameter('is_active', true)
            ->getQuery()
            ->getResult();
    }
}
