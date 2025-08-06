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

    public function findPreviousWithOffset(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
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

    public function findForLastMonth(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.createdAt >= :from')
            ->setParameter('from', (new \DateTime("-30 days"))->setTime(0, 0))
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPreviousToDate(\DateTimeInterface $date, int $limit = 1): ?array
    {
        return $this->createQueryBuilder('g')
            ->where('g.createdAt < :date')
            ->setParameter('date', (clone $date)->setTime(0, 0))
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findNextToDate(\DateTimeInterface $date, int $limit = 1): ?array
    {
        return $this->createQueryBuilder('g')
            ->where('g.createdAt > :date')
            ->setParameter('date', (clone $date)->setTime(23, 59, 59))
            ->orderBy('g.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
