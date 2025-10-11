<?php

namespace App\Service\Processable\Creators;

use App\Entity\Process\ScheduledProcess;
use App\Service\Processable\TurnOffHeatingProcess;
use App\Service\Processable\TurnOnHeatingProcess;

class HeatingPumpProcessCreator
{
    public function create(bool $enablePumps, \DateTimeImmutable $date, ?array $conditions = null): ScheduledProcess
    {
        return (new ScheduledProcess())
            ->setName($enablePumps ? TurnOnHeatingProcess::NAME : TurnOffHeatingProcess::NAME)
            ->setScheduledAt($date)
            ->setConditions($conditions)
        ;
    }
}
