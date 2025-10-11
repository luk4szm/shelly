<?php

namespace App\Service\Processable;

use App\Entity\Process\RecurringProcess;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.processable.recurring')]
interface RecurringProcessInterface
{
    public function process(RecurringProcess $process): void;
}
