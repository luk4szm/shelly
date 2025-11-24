<?php

namespace App\Service\Processable;

use App\Entity\Process\Process;
use App\Entity\Process\RecurringProcess;
use App\Repository\Process\ProcessRepository;
use App\Repository\UserRepository;
use App\Service\LogReader\GarageLogReader;
use App\Service\Shelly\Cover\ShellyGarageService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class CheckGarageStateProcess extends AbstractRecurringProcess implements AbstractProcessableInterface, RecurringProcessInterface
{
    public function __construct(
        #[AutowireIterator('app.shelly.process_condition')] iterable $processConditions,
        private readonly ProcessRepository                           $processRepository,
        private readonly UserRepository                              $userRepository,
        private readonly ShellyGarageService                         $garageService,
        private readonly GarageLogReader                             $garageLogReader,
        private readonly MailerInterface                             $mailer,
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
            // timestamp with last garage roller action
            $lastActivity = $this->garageLogReader->getParsedLogs(1)[0]['timestamp'];

            /** @var \DateTimeImmutable $lastActivity */
            if ($lastActivity->modify("+1 hour") < new \DateTimeImmutable()) {
                foreach ($this->userRepository->findInmates() as $inmate) {
                    $message = (new Email())
                        ->from(new Address(
                            $_ENV['MAILER_SENDER_NAME'],
                            $_ENV['MAILER_SENDER_MAIL'],
                        ))
                        ->to($inmate->getEmail())
                        ->subject('[HA_MSG] Garage is open')
                        ->text('It\'s dark outside, and the garage is still open. Time to close up!');

                    $this->mailer->send($message);
                }
            }
        }

        $process->setLastRunAt(new \DateTime());

        $this->processRepository->save($process);
    }
}
