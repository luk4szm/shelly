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
}
