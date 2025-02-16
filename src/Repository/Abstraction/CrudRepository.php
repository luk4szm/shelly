<?php

namespace App\Repository\Abstraction;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class CrudRepository extends ServiceEntityRepository
{
    public function save($data, bool $flush = true): self
    {
        $this->processData($data, 'persist', $flush);

        return $this;
    }

    public function remove($data, bool $flush = true): self
    {
        $this->processData($data, 'remove', $flush);

        return $this;
    }

    public function refresh($data): self
    {
        $this->processData($data, 'refresh', false);

        return $this;
    }

    public function detach(object $data): self
    {
        $this->_em->detach($data);

        return $this;
    }

    private function processData($data, string $method, bool $flush): void
    {
        if (is_iterable($data)) {
            foreach ($data as $entity) {
                $this->processEntity($entity, $method);
            }
        } else {
            $this->processEntity($data, $method);
        }

        if ($flush === true) {
            $this->_em->flush();
        }
    }

    private function processEntity(object $entity, string $method): void
    {
        $this->checkEntity($entity);

        $this->_em->$method($entity);
    }

    private function checkEntity(object $entity): void
    {
        if (in_array(get_class($entity), [$this->getEntityName(), 'Proxies\__CG__\\'.$this->getEntityName()]))
        {
            return;
        }

        foreach(get_declared_classes() as $class)
        {
            if (is_subclass_of(get_class($entity), $class))
            {
                return;
            }
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Repository has to operate only on "%s" entity or its subclasses. Class "%s" given',
                $this->getEntityName(),
                get_class($entity)
            )
        );
    }
}
