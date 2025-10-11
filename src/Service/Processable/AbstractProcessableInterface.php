<?php

namespace App\Service\Processable;

use App\Entity\Process\Process;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.processable')]
interface AbstractProcessableInterface
{
    public function isSupported(string $processName): bool;

    public function canBeExecuted(Process $process): bool;

    public function process(Process $process): void;
}
