<?php

namespace App\Repository;

use App\Entity\Config;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<Config>
 */
class ConfigRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    public function getValueByName(string $name): mixed
    {
        return $this->createQueryBuilder('c')
            ->select('c.value')
            ->andWhere('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAllValues(): mixed
    {
        $result =  $this->createQueryBuilder('c')
            ->select('c.name, c.value')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'value', 'name');
    }

    public function updateValueByName(string $name, mixed $value): int
    {
        return $this->createQueryBuilder('c')
            ->update()
            ->set('c.value', ':value')
            ->where('c.name = :name')
            ->setParameter('value', $value)
            ->setParameter('name', $name)
            ->getQuery()
            ->execute();
    }
}
