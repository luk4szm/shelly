<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SuplaGateOpenEvent extends Event
{
    public function __construct(
        private readonly string $method,
        private readonly string $userEmail,
    ) {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }
}
