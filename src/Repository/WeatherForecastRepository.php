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

    public function findForecastForNextDay(): array
    {
        return $this->createQueryBuilder('wf')
            ->where('wf.time >= :start')
            ->andWhere('wf.time <= :end')
            ->setParameter('start', new \DateTime("-1 hour"))
            ->setParameter('end', new \DateTime("+24 hours"))
            ->getQuery()
            ->getResult();
    }
}
