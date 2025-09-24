<?php

namespace App\Repository;

use App\Entity\HeatingNote;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<HeatingNote>
 */
class HeatingNoteRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeatingNote::class);
    }

    public function findForDate(\DateTime $date): array
    {
        return $this->createQueryBuilder('hn')
            ->where('hn.time >= :from')
            ->andWhere('hn.time <= :to')
            ->setParameter('from', (clone $date)->setTime(0, 0))
            ->setParameter('to', (clone $date)->setTime(23, 59, 59))
            ->orderBy('hn.time', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
