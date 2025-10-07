<?php

namespace App\Service\Processable;

use App\Entity\Process\ScheduledProcess;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.processable.scheduled')]
interface ScheduledProcessInterface
{
    public function isSupported(string $processName): bool;

    public function process(ScheduledProcess $process): void;
}
