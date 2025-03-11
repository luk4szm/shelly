<?php

namespace App\Repository;

use App\Entity\Hook;
use App\Model\DateRange;
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

    public function findLastPowerHookForDevice(string $device): array
    {
        $hooks = $this->createQueryBuilderForHooksByDevice($device)
            ->andWhere('hook.createdAt >= :date')
            ->setParameter('date', new \DateTime("-7 day"))
            ->orderBy('hook.id', 'DESC')
            ->getQuery()
            ->getResult();

        if (!count($hooks) < 250) {
            return $hooks;
        }

        return $this->createQueryBuilderForHooksByDevice($device)
            ->setMaxResults(250)
            ->orderBy('hook.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findHooksByDeviceForDateRange(string $device, DateRange $dateRange): array
    {
        return $this->createQueryBuilderForHooksByDevice($device)
            ->andWhere('hook.createdAt >= :from')
            ->andWhere('hook.createdAt <= :to')
            ->setParameter('from', $dateRange->getFrom())
            ->setParameter('to', $dateRange->getTo())
            ->orderBy('hook.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findHooksByDeviceAndDate(string $device, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilderForHooksByDevice($device, $date)
            ->getQuery()
            ->getResult();
    }

    public function findLastHookOfDay(string $device, \DateTimeInterface $date): ?Hook
    {
        return $this->createQueryBuilderForHooksByDevice($device, $date)
            ->orderBy('hook.id', order: 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createQueryBuilderForHooksByDevice(string $device, ?\DateTimeInterface $date = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('hook')
            ->andWhere('hook.device = :device')
            ->andWhere('hook.property = :property')
            ->setParameter('device', $device)
            ->setParameter('property', 'power');

        if ($date !== null) {
            $startDate = (clone $date)->setTime(0, 0);
            $endDate   = (clone $date)->setTime(23, 59, 59);

            $qb = $qb->andWhere('hook.createdAt >= :start')
                ->andWhere('hook.createdAt <= :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate);
        }

        return $qb;
    }
}
