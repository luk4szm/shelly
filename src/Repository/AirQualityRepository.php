<?php

namespace App\Repository;

use App\Entity\AirQuality;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    /**
     * Zwraca dane miesięczne pogrupowane po dniach:
     * dla każdego dnia i parametru: open(first), close(last), low(min), high(max).
     * Zwraca tablicę rekordów posortowaną po dniu rosnąco.
     */
    public function findCandleDataForMonth(\DateTimeInterface $date): array
    {
        $from = (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
        $to   = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

        $conn = $this->getEntityManager()->getConnection();

        // Uwaga: korzystamy z zmiennych użytkownika, aby wyłuskać first/last per day bez window functions.
        $sql = <<<SQL
SELECT
  d.day as day,
  -- PM2.5
  (SELECT aq.pm25 FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at ASC LIMIT 1)  AS pm25_open,
  (SELECT aq.pm25 FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at DESC LIMIT 1) AS pm25_close,
  (SELECT MIN(aq.pm25) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                               AS pm25_low,
  (SELECT MAX(aq.pm25) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                               AS pm25_high,

  -- PM10
  (SELECT aq.pm10 FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at ASC LIMIT 1)  AS pm10_open,
  (SELECT aq.pm10 FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at DESC LIMIT 1) AS pm10_close,
  (SELECT MIN(aq.pm10) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                                 AS pm10_low,
  (SELECT MAX(aq.pm10) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                                 AS pm10_high,

  -- Temperature
  (SELECT aq.temperature FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at ASC LIMIT 1)  AS temperature_open,
  (SELECT aq.temperature FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at DESC LIMIT 1) AS temperature_close,
  (SELECT MIN(aq.temperature) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                                  AS temperature_low,
  (SELECT MAX(aq.temperature) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                                  AS temperature_high,

  -- Humidity
  (SELECT aq.humidity FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at ASC LIMIT 1)  AS humidity_open,
  (SELECT aq.humidity FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at DESC LIMIT 1) AS humidity_close,
  (SELECT MIN(aq.humidity) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                                  AS humidity_low,
  (SELECT MAX(aq.humidity) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                                  AS humidity_high,

  -- Sea level pressure
  (SELECT aq.sea_level_pressure FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at ASC LIMIT 1)  AS seaLevelPressure_open,
  (SELECT aq.sea_level_pressure FROM air_quality aq WHERE DATE(aq.measured_at) = d.day ORDER BY aq.measured_at DESC LIMIT 1) AS seaLevelPressure_close,
  (SELECT MIN(aq.sea_level_pressure) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                                  AS seaLevelPressure_low,
  (SELECT MAX(aq.sea_level_pressure) FROM air_quality aq WHERE DATE(aq.measured_at) = d.day)                                  AS seaLevelPressure_high

FROM (
  SELECT DATE(aq.measured_at) AS day
  FROM air_quality aq
  WHERE aq.measured_at BETWEEN :from AND :to
  GROUP BY DATE(aq.measured_at)
) d
ORDER BY d.day ASC
SQL;

        $stmt   = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'from' => $from->format('Y-m-d H:i:s'),
            'to'   => $to->format('Y-m-d H:i:s'),
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Proste średnie i skrajne wartości dla całego miesiąca (na potrzeby kart).
     */
    public function findAverageForMonth(\DateTimeInterface $date): ?array
    {
        $from = (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
        $to   = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('aq')
            ->select('AVG(aq.pm25) as pm25', 'AVG(aq.pm10) as pm10', 'AVG(aq.temperature) as temperature', 'AVG(aq.humidity) as humidity', 'AVG(aq.seaLevelPressure) as seaLevelPressure')
            ->addSelect('MAX(aq.pm25) as pm25_max', 'MAX(aq.pm10) as pm10_max', 'MAX(aq.temperature) as temperature_max', 'MAX(aq.humidity) as humidity_max', 'MAX(aq.seaLevelPressure) as seaLevelPressure_max')
            ->addSelect('MIN(aq.pm25) as pm25_min', 'MIN(aq.pm10) as pm10_min', 'MIN(aq.temperature) as temperature_min', 'MIN(aq.humidity) as humidity_min', 'MIN(aq.seaLevelPressure) as seaLevelPressure_min')
            ->where('aq.measuredAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
