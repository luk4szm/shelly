<?php

namespace App\Event\Hook;

use Symfony\Contracts\EventDispatcher\Event;

class InsolationHookEvent extends Event
{
    public function __construct(
        private readonly float $insolation
    ) {
    }

    public function getInsolation(): float
    {
        return $this->insolation;
    }
}
