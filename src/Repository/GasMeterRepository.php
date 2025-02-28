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
}
