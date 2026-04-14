<?php

namespace App\Service\Processable;

use App\Entity\Process\HydrationProcess;
use App\Entity\Process\Process;
use App\Repository\Process\HydrationProcessRepository;
use App\Service\Hydration\HydrationDeviceFinder;
use App\Service\Shelly\Switch\HydrationValveService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class StartHydrationProcess extends AbstractProcess implements AbstractProcessableInterface, HydrationProcessInterface
{
    public function __construct(
        #[AutowireIterator('app.shelly.process_condition')] iterable $processConditions,
        private readonly HydrationValveService                       $valveService,
        private readonly HydrationDeviceFinder                       $deviceFinder,
        private readonly HydrationProcessRepository                  $repository,
    ) {
        parent::__construct($processConditions);
    }

    public const NAME = 'hydration-start';

    public function process(Process $process): void
    {
        /** @var HydrationProcess $process */
        $valve = $this->deviceFinder->getByName($process->getValve());

        $this->valveService->start($valve, $process->getDuration());

        $process->setExecutedAt(new \DateTimeImmutable());

        $this->repository->save($process);
    }
}
