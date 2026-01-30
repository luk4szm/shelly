<?php

namespace App\Repository;

use App\Entity\Sms;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<Sms>
 */
class SmsRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sms::class);
    }

    public function findPreviousSmsForTrigger(string $trigger): ?Sms
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.developer = :trigger')
            ->setParameter('trigger', $trigger)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
