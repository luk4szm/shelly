<?php

namespace App\Repository;

use App\Entity\Scene;
use App\Repository\Abstraction\CrudRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CrudRepository<Scene>
 */
class SceneRepository extends CrudRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scene::class);
    }
}
