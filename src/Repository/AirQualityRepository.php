<?php

namespace App\Repository;

use App\Entity\AirQuality;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<AirQuality>
 */
class AirQualityRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AirQuality::class);
    }

    public function findLast(): ?AirQuality
    {
        return $this->createQueryBuilder('aq')
            ->orderBy('aq.measuredAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('aq')
            ->where('aq.measuredAt >= :from')
            ->andWhere('aq.measuredAt <= :to')
            ->setParameter('from', (clone $date)->setTime(0, 0))
            ->setParameter('to', (clone $date)->setTime(23, 59, 59))
            ->getQuery()
            ->getResult();
    }

    public function findAverageForDate(\DateTimeInterface $date): ?array
    {
        dump($date);

        return $this->createQueryBuilder('aq')
            ->select('AVG(aq.pm25) as pm25', 'AVG(aq.pm10) as pm10', 'AVG(aq.temperature) as temperature', 'AVG(aq.humidity) as humidity', 'AVG(aq.seaLevelPressure) as seaLevelPressure')
            ->addSelect('MAX(aq.pm25) as pm25_max', 'MAX(aq.pm10) as pm10_max', 'MAX(aq.temperature) as temperature_max', 'MAX(aq.humidity) as humidity_max', 'MAX(aq.seaLevelPressure) as seaLevelPressure_max')
            ->addSelect('MIN(aq.pm25) as pm25_min', 'MIN(aq.pm10) as pm10_min', 'MIN(aq.temperature) as temperature_min', 'MIN(aq.humidity) as humidity_min', 'MIN(aq.seaLevelPressure) as seaLevelPressure_min')
            ->where('aq.measuredAt >= :from')
            ->andWhere('aq.measuredAt <= :to')
            ->setParameter('from', (clone $date)->setTime(0, 0))
            ->setParameter('to', (clone $date)->setTime(23, 59, 59))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
