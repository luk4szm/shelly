<?php

namespace App\Repository;

use App\Entity\WeatherForecast;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<WeatherForecast>
 */
class WeatherForecastRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeatherForecast::class);
    }

    public function findForecast(): array
    {
        return $this->createQueryBuilder('wf')
            ->where('wf.time >= :start')
            ->setParameter('start', new \DateTime("-1 hour"))
            ->getQuery()
            ->getResult();
    }

    public function findForestForRestOfDay(\DateTimeInterface $date): array
    {
        $start = $date->format('Y-m-d') === (new \DateTime())->format('Y-m-d')
            ? new \DateTime()
            : $date;

        $end = (clone $start)->modify("+1 day")->setTime(0, 0);

        return $this->createQueryBuilder('wf')
            ->where('wf.time >= :start')
            ->andWhere('wf.time <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function findForecastForDate(?\DateTimeInterface $date = null): ?WeatherForecast
    {
        return $this->createQueryBuilder('wf')
            ->where('wf.time >= :start')
            ->setParameter('start', ($date ?? new \DateTime())->modify("-1 hour"))
            ->orderBy('wf.time', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array{sum: float, lastAt: \DateTimeImmutable|null}
     * @throws \Exception
     */
    public function getSumRainfallSince(?\DateTimeImmutable $date = null): array
    {
        $since = $date ?? new \DateTimeImmutable('-24 hours');

        $result = $this->createQueryBuilder('wf')
            ->select('SUM(wf.precipitation) as total_rainfall')
            ->addSelect('MAX(CASE WHEN wf.precipitation > 0 THEN wf.time ELSE :null END) as last_rain_at')
            ->where('wf.time >= :since')
            ->setParameter('since', $since)
            ->setParameter('null', null)
            ->getQuery()
            ->getSingleResult();

        return [
            'sum'    => (float)($result['total_rainfall'] ?? 0),
            'lastAt' => $result['last_rain_at'] ? new \DateTimeImmutable($result['last_rain_at']) : null,
        ];
    }

    /**
     * @return array{sum: float, startAt: \DateTimeImmutable|null}
     * @throws \Exception
     */
    public function getForecastedRainfallNext24h(): array
    {
        $start = (new \DateTimeImmutable())->setTime((int)date('H'), 0);
        $end   = $start->modify('+24 hours');

        $result = $this->createQueryBuilder('wf')
            ->select('SUM(wf.precipitation) as total_rainfall')
            ->addSelect('MIN(CASE WHEN wf.precipitation > 0 THEN wf.time ELSE :null END) as first_rain_at')
            ->where('wf.time >= :start')
            ->andWhere('wf.time < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('null', null)
            ->getQuery()
            ->getSingleResult();

        return [
            'sum'     => (float) ($result['total_rainfall'] ?? 0),
            'startAt' => $result['first_rain_at'] ? new \DateTimeImmutable($result['first_rain_at']) : null,
        ];
    }
}
