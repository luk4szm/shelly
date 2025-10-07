<?php

namespace App\Service\Processable;

abstract class AbstractProcess
{
    public function isSupported(string $processName): bool
    {
        return $processName === $this::NAME;
    }
}
