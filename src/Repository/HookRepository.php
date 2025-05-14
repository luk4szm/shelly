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

    public function findActualTemps(array $locations): array
    {
        foreach ($locations as $location) {
            $temps[] = $this->createQueryBuilder('hook')
                ->where('hook.device = :device')
                ->andWhere('hook.property = :property')
                ->setParameter('device', $location)
                ->setParameter('property', 'temp')
                ->orderBy('hook.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $temps;
    }

    public function findLocationTemperatures(
        \DateTime    $from,
        \DateTime    $to,
        string|array $location = 'all',
    ): array
    {
        $qb = $this->createQueryBuilder('hook')
            ->where('hook.property = :property')
            ->setParameter('property', 'temp')
            ->orderBy('hook.id', 'ASC')
        ;

        if (is_array($location)) {
            $qb->andWhere('hook.device in (:location)')
               ->setParameter('location', $location);
        } elseif ($location !== 'all') {
            $qb->andWhere('hook.device = :location')
               ->setParameter('location', $location);
        }

        $qb->andWhere('hook.createdAt >= :from')
           ->andWhere('hook.createdAt <= :to')
           ->setParameter('from', $from)
           ->setParameter('to', $to);

        return $qb->getQuery()->getResult();
    }

    public function findGroupedHooks(string $device, string $property): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DATE(created_at) AS date, ROUND(AVG(VALUE), 1) AS avg,  ROUND(MAX(VALUE), 1) AS max,  ROUND(MIN(VALUE), 1) AS min
            FROM hook
            WHERE device = :device AND property = :property AND created_at >= :from
            GROUP BY date
        ';

        $resultSet = $conn->executeQuery($sql, [
            'device' => $device,
            'property' => $property,
            'from' => (new \DateTime("-1 month"))->format("Y-m-d"),
        ]);

        // returns an array of arrays (i.e. a raw data set)
        return $resultSet->fetchAllAssociative();
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
