<?php

namespace App\Command;

use App\Exception\ShellyRateLimitException;
use Psr\Log\LoggerInterface;
use App\Entity\Process\RecurringProcess;
use App\Entity\Process\ScheduledProcess;
use App\Repository\Process\HydrationProcessRepository;
use App\Repository\Process\RecurringProcessRepository;
use App\Repository\Process\ScheduledProcessRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'shelly:process:consume',
    description: 'Consume and execute due scheduled and active recurring processes.',
)]
class ShellyProcessConsumeCommand extends Command
{
    private int $failedProcesses = 0;

    public function __construct(
        #[AutowireIterator('app.shelly.processable.recurring')] private readonly iterable $recurringProcessable,
        #[AutowireIterator('app.shelly.processable.scheduled')] private readonly iterable $scheduledProcessable,
        #[AutowireIterator('app.shelly.processable.hydration')] private readonly iterable $hydrationProcessable,
        private readonly RecurringProcessRepository                                       $recurringRepository,
        private readonly ScheduledProcessRepository                                       $scheduledRepository,
        private readonly HydrationProcessRepository                                       $hydrationRepository,
        private readonly LoggerInterface                                                  $logger,
        private int                                                                       $executedProcesses = 0,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->executedProcesses = 0;
        $this->failedProcesses   = 0;

        $this->executeHydrationProcesses();
        $this->executeRecurringProcesses();
        $this->executeScheduledProcesses();

        if ($this->failedProcesses > 0) {
            $io->error(sprintf('Executed %d process(es); %d failed due to the Shelly Cloud rate limit.', $this->executedProcesses, $this->failedProcesses));

            return Command::FAILURE;
        }

        if ($this->executedProcesses > 0) {
            $io->success(sprintf('Executed %d process(es).', $this->executedProcesses));
        } else {
            $io->success('No processes executed.');
        }

        return Command::SUCCESS;
    }

    private function executeHydrationProcesses(): void
    {
        $processes = $this->hydrationRepository->findProcessToExecute();

        if (empty($processes)) {
            return;
        }

        /** @var ScheduledProcess $process */
        $this->handleProcesses($processes, $this->hydrationProcessable);
    }

    private function executeRecurringProcesses(): void
    {
        $processes = $this->recurringRepository->findProcessToExecute();
        if (empty($processes)) {
            return;
        }

        /** @var RecurringProcess $process */
        $this->handleProcesses($processes, $this->recurringProcessable);
    }

    private function executeScheduledProcesses(): void
    {
        $processes = $this->scheduledRepository->findProcessToExecute();
        if (empty($processes)) {
            return;
        }

        /** @var ScheduledProcess $process */
        $this->handleProcesses($processes, $this->scheduledProcessable);
    }

    /**
     * @param iterable<int, ScheduledProcess|RecurringProcess> $processes
     * @param iterable<int, object>                            $consumers
     */
    private function handleProcesses(iterable $processes, iterable $consumers): void
    {
        foreach ($processes as $process) {
            if (null === $processName = $process->getName()) {
                continue;
            }

            foreach ($consumers as $consumer) {
                if (!$consumer->isSupported($processName)) {
                    continue;
                }

                try {
                    if ($consumer->canBeExecuted($process))
                    {
                        $consumer->process($process);

                        $this->executedProcesses++;

                        // jic to avoid shelly's 429 error
                        sleep(1);
                    }
                } catch (ShellyRateLimitException $e) {
                    ++$this->failedProcesses;
                    $this->logger->error($e->getMessage(), ['process' => $processName, 'process_id' => $process->getId()]);
                }

                break;
            }
        }
    }
}
