<?php

namespace App\Command;

use App\Service\Device\DeviceFinder;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'shelly:device:history',
    description: 'Returns the history of starting the device on a given day',
)]
class ShellyDeviceDailyHistoryCommand extends StatusHelperCommand
{
    public function __construct(
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable     $statusHelpers,
        DeviceFinder $deviceFinder,
    ) {
        parent::__construct($statusHelpers, $deviceFinder);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('device', InputArgument::OPTIONAL, 'Device name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io           = new SymfonyStyle($input, $output);
        $device       = $this->getDevice($input, $output);
        $statusHelper = $this->getDeviceHelper($device);

        /** @var ArrayCollection $history */
        if (null === $history = $statusHelper->getHistory(grouped: true)) {
            $io->warning('No device information found');

            return self::SUCCESS;
        }

        $io->title(
            sprintf(
                '[%s] Retrieving history of the "%s" device',
                (new \DateTime())->format('H:i:s'),
                $device
            )
        );

        $io->table(
            ['date', 'runtime', 'pause'],
            $history->map(function (array $entry) {
                return [
                    isset($entry['running']) ? $entry['running']->getStartTime()->format('d.m.Y H:i:s') : null,
                    isset($entry['running']) ? $entry['running']->getStatusDurationReadable() : null,
                    isset($entry['standby']) ? $entry['standby']->getStatusDurationReadable() : null,
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }
}
