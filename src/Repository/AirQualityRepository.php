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
        return $this->createQueryBuilder('aq')
            ->select('AVG(aq.pm25)', 'AVG(aq.pm10)', 'AVG(aq.temperature)', 'AVG(aq.humidity)', 'AVG(aq.pressure)')
            ->where('aq.measuredAt >= :from')
            ->andWhere('aq.measuredAt <= :to')
            ->setParameter('from', (clone $date)->setTime(0, 0))
            ->setParameter('to', (clone $date)->setTime(23, 59, 59))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
