<?php

namespace App\Service\Processable;

use App\Entity\Process\Process;
use App\Entity\Process\ScheduledProcess;
use App\Repository\Process\ProcessRepository;
use App\Service\Shelly\Switch\HeatingPumpsService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class TurnOffHeatingProcess extends AbstractProcess implements AbstractProcessableInterface, ScheduledProcessInterface
{
    public function __construct(
        #[AutowireIterator('app.shelly.process_condition')] iterable $processConditions,
        private readonly HeatingPumpsService                         $pumpsService,
        private readonly ProcessRepository                           $repository,
    ) {
        parent::__construct($processConditions);
    }

    public const NAME = 'turn-off-heating-pumps';

    public function process(Process $process): void
    {
        $this->pumpsService->turnOff();

        /** @var ScheduledProcess $process */
        $process->setExecutedAt(new \DateTimeImmutable());

        $this->repository->save($process);
    }
}
