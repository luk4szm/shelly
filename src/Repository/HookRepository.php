<?php

namespace App\Repository;

use App\Entity\Hook;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<Hook>
 */
class HookRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hook::class);
    }

    public function findByDeviceAndProperty(string $device, string $property): array
    {
        return $this->createQueryBuilder('hook')
            ->andWhere('hook.device = :device')
            ->andWhere('hook.property = :property')
            ->setParameter('device', $device)
            ->setParameter('property', $property)
            ->orderBy('hook.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLastActiveByDevice(string $device): array
    {
        return $this->createQueryBuilder('hook')
            ->andWhere('hook.device = :device')
            ->andWhere('hook.property = :property')
            ->andWhere('hook.createdAt >= :date')
            ->setParameter('device', $device)
            ->setParameter('property', 'power')
            ->setParameter('date', new \DateTime("-1 day"))
            ->orderBy('hook.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findHooksByDeviceAndDate(string $device, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilderForHooksByDate($device, $date)
            ->getQuery()
            ->getResult();
    }

    public function findLastHookOfDay(string $device, \DateTimeInterface $date): ?Hook
    {
        return $this->createQueryBuilderForHooksByDate($device, $date)
            ->orderBy('hook.id', order: 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createQueryBuilderForHooksByDate(string $device, \DateTimeInterface $date): QueryBuilder
    {
        $startDate = (clone $date)->setTime(0, 0);
        $endDate = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('hook')
            ->andWhere('hook.device = :device')
            ->andWhere('hook.property = :property')
            ->andWhere('hook.createdAt >= :start')
            ->andWhere('hook.createdAt <= :end')
            ->setParameter('device', $device)
            ->setParameter('property', 'power')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);
    }
}
