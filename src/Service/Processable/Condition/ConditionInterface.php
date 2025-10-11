<?php

namespace App\Service\Processable\Condition;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.process_condition')]
interface ConditionInterface
{
    public function isSatisfied(): bool;
}
