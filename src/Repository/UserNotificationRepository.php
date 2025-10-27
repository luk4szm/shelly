<?php

namespace App\Repository;

use App\Entity\UserNotification;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<UserNotification>
 */
class UserNotificationRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    public function findToExecuteByEvent(string $event): array
    {
        return $this->createQueryBuilder('un')
            ->andWhere('un.executedAt IS NULL')
            ->andWhere('un.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getResult();
    }
}
