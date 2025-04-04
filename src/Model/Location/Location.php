<?php

namespace App\Model\Location;

abstract class Location
{
    public function getName(): string
    {
        return $this::NAME;
    }

    public static function getHeatingLocations(): array
    {
        return [
            Buffer::NAME,
            FloorSupply::NAME,
            FloorReturn::NAME,
        ];
    }
}
