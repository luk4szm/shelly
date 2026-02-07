<?php

namespace App\Service\Processable;

use App\Entity\Process\Process;
use App\Entity\Process\RecurringProcess;
use App\Model\Device\BedLeds;
use App\Repository\Process\ProcessRepository;
use App\Service\Shelly\Light\ShellyLightService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class TurnOffBedLightsProcess extends AbstractRecurringProcess implements AbstractProcessableInterface, RecurringProcessInterface
{
    public function __construct(
        #[AutowireIterator('app.shelly.process_condition')] iterable $processConditions,
        private readonly ProcessRepository                           $processRepository,
        private readonly ShellyLightService                          $lightService,
    ) {
        parent::__construct($processConditions);
    }

    public const NAME = 'turn-off-bed-lights';

    /**
     * @param RecurringProcess $process
     * @return void
     */
    public function process(Process $process): void
    {
        $this->lightService->turnOff(BedLeds::DEVICE_ID, BedLeds::CHANNEL);

        $process->setLastRunAt(new \DateTime());

        $this->processRepository->save($process);
    }
}
