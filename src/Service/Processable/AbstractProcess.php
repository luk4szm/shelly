<?php

namespace App\Service\Processable;

use App\Entity\Process\Process;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

abstract class AbstractProcess
{
    public function __construct(
        #[AutowireIterator('app.shelly.process_condition')]
        private readonly iterable $processConditions,
    ) {}

    public function isSupported(string $processName): bool
    {
        return $processName === $this::NAME;
    }

    /**
     * If the process has conditions, all of they must be satisfied.
     *
     * @param Process $process
     * @return bool
     */
    public function canBeExecuted(Process $process): bool
    {
        if (empty($process->getConditions())) {
            return true;
        }

        foreach ($process->getConditions() as $condition) {
            foreach ($this->processConditions as $processCondition) {
                if (!$processCondition->supports($condition)) {
                    continue;
                }

                if (!$processCondition->isSatisfied($condition)) {
                    return false;
                }
            }
        }

        return true;
    }
}
