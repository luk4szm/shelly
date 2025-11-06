<?php

namespace App\Repository\Process;

use App\Entity\Process\ScheduledProcess;
use App\Repository\Abstraction\CrudRepository;
use App\Service\Processable\TurnOffHeatingProcess;
use App\Service\Processable\TurnOnHeatingProcess;
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

    public function findNextProcessToExecute(string $name): ?ScheduledProcess
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.executedAt IS NULL')
            ->andWhere('p.name = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->orderBy('p.scheduledAt', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteScheduledHeatingProcesses(): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->where('p.name IN (:names)')
            ->andWhere('p.executedAt IS NULL')
            ->setParameter('names', [TurnOnHeatingProcess::NAME, TurnOffHeatingProcess::NAME])
            ->getQuery()
            ->execute();
    }
}
