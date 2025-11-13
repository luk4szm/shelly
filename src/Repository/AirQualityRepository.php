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
            ->select('AVG(aq.pm25) as pm25', 'AVG(aq.pm10) as pm10', 'AVG(aq.temperature) as temperature', 'AVG(aq.perceivedTemperature) as perceivedTemperature', 'AVG(aq.humidity) as humidity', 'AVG(aq.seaLevelPressure) as seaLevelPressure')
            ->addSelect('MAX(aq.pm25) as pm25_max', 'MAX(aq.pm10) as pm10_max', 'MAX(aq.temperature) as temperature_max', 'MAX(aq.perceivedTemperature) as perceivedTemperature_max', 'MAX(aq.humidity) as humidity_max', 'MAX(aq.seaLevelPressure) as seaLevelPressure_max')
            ->addSelect('MIN(aq.pm25) as pm25_min', 'MIN(aq.pm10) as pm10_min', 'MIN(aq.temperature) as temperature_min', 'MIN(aq.perceivedTemperature) as perceivedTemperature_min', 'MIN(aq.humidity) as humidity_min', 'MIN(aq.seaLevelPressure) as seaLevelPressure_min')
            ->where('aq.measuredAt >= :from')
            ->andWhere('aq.measuredAt <= :to')
            ->setParameter('from', (clone $date)->setTime(0, 0))
            ->setParameter('to', (clone $date)->setTime(23, 59, 59))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Średnie i skrajne wartości dla całego miesiąca (na karty).
     */
    public function findAverageForMonth(\DateTimeInterface $date): ?array
    {
        $from = (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
        $to   = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('aq')
            ->select('AVG(aq.pm25) as pm25', 'AVG(aq.pm10) as pm10', 'AVG(aq.temperature) as temperature', 'AVG(aq.perceivedTemperature) as perceivedTemperature', 'AVG(aq.humidity) as humidity', 'AVG(aq.seaLevelPressure) as seaLevelPressure')
            ->addSelect('MAX(aq.pm25) as pm25_max', 'MAX(aq.pm10) as pm10_max', 'MAX(aq.temperature) as temperature_max', 'MAX(aq.perceivedTemperature) as perceivedTemperature_max', 'MAX(aq.humidity) as humidity_max', 'MAX(aq.seaLevelPressure) as seaLevelPressure_max')
            ->addSelect('MIN(aq.pm25) as pm25_min', 'MIN(aq.pm10) as pm10_min', 'MIN(aq.temperature) as temperature_min', 'MIN(aq.perceivedTemperature) as perceivedTemperature_min', 'MIN(aq.humidity) as humidity_min', 'MIN(aq.seaLevelPressure) as seaLevelPressure_min')
            ->where('aq.measuredAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Dobowe średnie (PM2.5, PM10) dla wskazanego miesiąca.
     * Zwraca: day (YYYY-MM-DD), pm25_avg, pm10_avg.
     */
    public function findDailyAveragesForRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
SELECT
  DATE(measured_at) AS day,
  AVG(pm25) AS pm25_avg,
  AVG(pm10) AS pm10_avg
FROM air_quality
WHERE measured_at >= :from AND measured_at <= :to
GROUP BY DATE(measured_at)
ORDER BY day ASC
SQL;

        $stmt   = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'from' => $from->format('Y-m-d H:i:s'),
            'to'   => $to->format('Y-m-d H:i:s'),
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Dane świeczkowe (open/high/low/close) dla parametrów atmosferycznych w rozbiciu dziennym.
     * Zwraca rekordy po dniach dla: temperature, humidity, sea_level_pressure.
     */
    public function findAtmosphereCandlesForRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
SELECT
    -- Dzień
    DATE(aq.measured_at) AS day,

    -- Temperature (Open/Close z użyciem złączeń do aq_open i aq_close)
    aq_open.temperature AS temperature_open,
    aq_close.temperature AS temperature_close,
    -- Temperature (Low/High z użyciem funkcji agregujących)
    MIN(aq.temperature) AS temperature_low,
    MAX(aq.temperature) AS temperature_high,

    -- Humidity
    aq_open.humidity AS humidity_open,
    aq_close.humidity AS humidity_close,
    MIN(aq.humidity) AS humidity_low,
    MAX(aq.humidity) AS humidity_high,

    -- Sea level pressure
    aq_open.sea_level_pressure AS seaLevelPressure_open,
    aq_close.sea_level_pressure AS seaLevelPressure_close,
    MIN(aq.sea_level_pressure) AS seaLevelPressure_low,
    MAX(aq.sea_level_pressure) AS seaLevelPressure_high

FROM air_quality aq

-- 1. Podzapytanie do obliczenia Min i Max `measured_at` dla każdego dnia
-- To jest kluczowe, aby znaleźć precyzyjny timestamp
JOIN (
    SELECT
        DATE(measured_at) AS day_key,
        MIN(measured_at) AS min_time,
        MAX(measured_at) AS max_time
    FROM air_quality
    WHERE measured_at >= :from AND measured_at <= :to
    GROUP BY day_key
) AS daily_times
    ON DATE(aq.measured_at) = daily_times.day_key

-- 2. Złączenie w celu pobrania wartości 'OPEN' (pierwszy pomiar dnia)
LEFT JOIN air_quality aq_open
    ON aq_open.measured_at = daily_times.min_time

-- 3. Złączenie w celu pobrania wartości 'CLOSE' (ostatni pomiar dnia)
LEFT JOIN air_quality aq_close
    ON aq_close.measured_at = daily_times.max_time

-- Grupowanie po dniu (dla MIN/MAX) oraz dołączenie wartości OPEN/CLOSE
GROUP BY
    day,
    aq_open.temperature, aq_close.temperature,
    aq_open.humidity, aq_close.humidity,
    aq_open.sea_level_pressure, aq_close.sea_level_pressure
ORDER BY day ASC;
SQL;

        $stmt   = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'from' => $from->format('Y-m-d H:i:s'),
            'to'   => $to->format('Y-m-d H:i:s'),
        ]);

        return $result->fetchAllAssociative();
    }

    public function findWithoutPerceivedTemperature(): array
    {
        return $this->createQueryBuilder('aq')
            ->where('aq.perceivedTemperature IS NULL')
            ->andWhere('aq.sensor IS NULL')
            ->getQuery()
            ->getResult();
    }
}
