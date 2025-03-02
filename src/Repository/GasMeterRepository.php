<?php

namespace App\Repository;

use App\Entity\GasMeter;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<GasMeter>
 */
class GasMeterRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GasMeter::class);
    }

    public function findLast(): ?GasMeter
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.createdAt >= :from')
            ->andWhere('g.createdAt <= :to')
            ->setParameter('from', (clone $date)->setTime(0, 0))
            ->setParameter('to', (clone $date)->setTime(23, 59, 59))
            ->getQuery()
            ->getResult();
    }

    public function findFirstPreviousToDate(\DateTimeInterface $date): ?GasMeter
    {
        return $this->createQueryBuilder('g')
            ->where('g.createdAt < :date')
            ->setParameter('date', (clone $date)->setTime(0, 0))
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findFirstNextToDate(\DateTimeInterface $date): ?GasMeter
    {
        return $this->createQueryBuilder('g')
            ->where('g.createdAt > :date')
            ->setParameter('date', (clone $date)->setTime(23, 59, 59))
            ->orderBy('g.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
