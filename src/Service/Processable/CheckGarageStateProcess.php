<?php

namespace App\Service\Processable;

use App\Entity\Process\Process;
use App\Entity\Process\RecurringProcess;
use App\Repository\Process\ProcessRepository;
use App\Repository\UserRepository;
use App\Service\Shelly\Cover\ShellyGarageService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class CheckGarageStateProcess extends AbstractRecurringProcess implements AbstractProcessableInterface, RecurringProcessInterface
{
    public function __construct(
        #[AutowireIterator('app.shelly.process_condition')] iterable $processConditions,
        private readonly ProcessRepository                           $processRepository,
        private readonly UserRepository                              $userRepository,
        private readonly ShellyGarageService                         $garageService,
    ) {
        parent::__construct($processConditions);
    }

    public const NAME = 'check-garage-cover-state';

    /**
     * @param RecurringProcess $process
     * @return void
     */
    public function process(Process $process): void
    {
        if ($this->garageService->isOpen()) {
            foreach ($this->userRepository->findInmates() as $inmate) {
                mail(
                    $inmate->getEmail(),
                    'Garage is open',
                    'It\'s dark outside, and the garage is still open. Time to close up!'
                );
            }
        }

        $process->setLastRunAt(new \DateTime());

        $this->processRepository->save($process);
    }
}
