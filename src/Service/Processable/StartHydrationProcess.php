<?php

namespace App\Service\Processable;

use App\Entity\Process\HydrationProcess;
use App\Entity\Process\Process;
use App\Exception\ShellyWriteOutcomeUnknownException;
use App\Repository\Process\HydrationProcessRepository;
use App\Service\Hydration\HydrationDeviceFinder;
use App\Service\Shelly\Switch\HydrationValveService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class StartHydrationProcess extends AbstractProcess implements AbstractProcessableInterface, HydrationProcessInterface
{
    public function __construct(
        #[AutowireIterator('app.shelly.process_condition')] iterable $processConditions,
        private readonly HydrationValveService                       $valveService,
        private readonly HydrationDeviceFinder                       $deviceFinder,
        private readonly HydrationProcessRepository                  $repository,
        private readonly LoggerInterface                             $logger,
    ) {
        parent::__construct($processConditions);
    }

    public const NAME = 'hydration-start';

    public function process(Process $process): void
    {
        /** @var HydrationProcess $process */
        $valve = $this->deviceFinder->getByName($process->getValve());

        try {
            $this->valveService->start($valve, $process->getDuration());
        } catch (ShellyWriteOutcomeUnknownException $exception) {
            // The device may have applied Light.Set before the response failed.
            // Mark the attempt as executed to avoid extending toggle_after on the next cron run.
            $process->setExecutedAt(new \DateTimeImmutable());
            $this->repository->save($process);
            $this->logger->error($exception->getMessage(), [
                'process_id' => $process->getId(),
                'valve'      => $valve->getName(),
            ]);

            return;
        }

        $process->setExecutedAt(new \DateTimeImmutable());

        $this->repository->save($process);
    }
}
