<?php

namespace App\Service\Processable\Condition;

abstract class Condition implements ConditionInterface
{
    public function supports(string $conditionName): bool
    {
        return $conditionName === $this::NAME;
    }
}
