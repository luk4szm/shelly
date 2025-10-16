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
}
