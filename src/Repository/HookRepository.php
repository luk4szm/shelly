<?php

namespace App\Repository;

use App\Entity\Hook;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<Hook>
 */
class HookRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hook::class);
    }

    public function findByDeviceAndProperty(string $device, string $property): array
    {
        return $this->createQueryBuilder('hook')
            ->andWhere('hook.device = :device')
            ->andWhere('hook.property = :property')
            ->setParameter('device', $device)
            ->setParameter('property', $property)
            ->orderBy('hook.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentPowerByDevice(string $device): array
    {
        return $this->createQueryBuilder('hook')
            ->andWhere('hook.device = :device')
            ->andWhere('hook.property = :property')
            ->setParameter('device', $device)
            ->setParameter('property', 'power')
            ->orderBy('hook.id', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
}
