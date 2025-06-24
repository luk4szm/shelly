<?php

namespace App\Event\Hook;

use App\Entity\Hook;
use Symfony\Contracts\EventDispatcher\Event;

class TvHookEvent extends Event
{
    public function __construct(
        private readonly Hook $hook
    ) {
    }

    public function getHook(): Hook
    {
        return $this->hook;
    }
}
