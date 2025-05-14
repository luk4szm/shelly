<?php

namespace App\Model\Location;

abstract class Location
{
    public function getName(): string
    {
        return $this::NAME;
    }

    public function getGroups(): array
    {
        return $this::GROUP;
    }
}
