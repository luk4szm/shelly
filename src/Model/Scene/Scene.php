<?php

namespace App\Model\Scene;

abstract class Scene implements SceneInterface
{
    public function getId(): int
    {
        return $this::ID;
    }

    public function getName(): string
    {
        return $this::NAME;
    }
}
