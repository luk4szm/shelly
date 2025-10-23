<?php

namespace App\Service\Processable;

use App\Entity\Process\Process;
use App\Entity\Process\RecurringProcess;

class AbstractRecurringProcess extends AbstractProcess
{
    /**
     * @param RecurringProcess $process
     * @return bool
     */
    public function canBeExecuted(Process $process): bool
    {
        $interval = $process->getTimeInterval();
        $modifier = $interval === null ? '+1 minute' : sprintf('+%d minutes', $interval);

        if (
            $process->getLastRunAt() !== null
            && clone($process->getLastRunAt())->modify($modifier) > new \DateTime()
        ) {
            return false;
        }

        return parent::canBeExecuted($process);
    }
}
