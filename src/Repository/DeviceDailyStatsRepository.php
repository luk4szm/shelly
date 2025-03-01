<?php

namespace App\Repository;

use App\Entity\DeviceDailyStats;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<DeviceDailyStats>
 */
class DeviceDailyStatsRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceDailyStats::class);
    }

    public function findForDeviceAndDay(string $device, \DateTimeInterface $date): ?DeviceDailyStats
    {
        return $this->createQueryBuilder('dds')
            ->where('dds.device = :device')
            ->andWhere('dds.date = :date')
            ->setParameter('device', $device)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForDeviceFromLastDays(string $device, int $days = 30): array
    {
        $to   = (new \DateTime());
        $from = (clone $to)->modify(sprintf("- %d days", $days));

        return $this->createQueryBuilder('dds')
            ->where('dds.device = :device')
            ->andWhere('dds.date >= :from')
            ->andWhere('dds.date <= :to')
            ->setParameter('device', $device)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('dds.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForDeviceAndMonth(string $device, \DateTimeInterface $month): array
    {
        $firstDayOfMonth = (clone $month)->modify('first day of this month');
        $lastDayOfMonth = (clone $month)->modify('last day of this month');

        return $this->createQueryBuilder('dds')
            ->where('dds.device = :device')
            ->andWhere('dds.date >= :firstDayOfMonth')
            ->andWhere('dds.date <= :lastDayOfMonth')
            ->setParameter('device', $device)
            ->setParameter('firstDayOfMonth', $firstDayOfMonth)
            ->setParameter('lastDayOfMonth', $lastDayOfMonth)
            ->orderBy('dds.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
