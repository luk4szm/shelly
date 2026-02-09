<?php

namespace App\Command;

use App\Model\Device\BedLeds;
use App\Service\Shelly\Light\ShellyLightService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:light',
    description: 'Control shelly lights command',
)]
class ShellyLightCommand extends Command
{
    public function __construct(
        private readonly ShellyLightService $lightService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('shelly_command', InputArgument::OPTIONAL, 'Command name ["on", "off", "status"]')
            ->addArgument('brightness', InputArgument::OPTIONAL, 'Set brightness')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $action = $input->getArgument('shelly_command') ?: $io->choice('Please select the shelly command', ["on", "off"]);

        if ($action === 'on') {
            $brightness = $input->getArgument('brightness') ?: $io->ask('Please type device brightness');
        }

        $response = match ($action) {
            'on'    => $this->lightService->turnOn(new BedLeds(), brightness: $brightness ?? 50, white: 50, colors: [123, 244, 41]),
            'off'   => $this->lightService->turnOff(new BedLeds()),
            default => throw new \Exception('Invalid command')
        };

        dump($response);

        return Command::SUCCESS;
    }
}
