<?php

namespace App\Service\Processable;

use App\Entity\Process\HydrationProcess;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.processable.hydration')]
interface HydrationProcessInterface
{
    public function process(HydrationProcess $process): void;
}
