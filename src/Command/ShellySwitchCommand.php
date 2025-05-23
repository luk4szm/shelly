<?php

namespace App\Command;

use App\Service\Shelly\Switch\ShellySwitchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:switch',
    description: 'Control shelly switch command',
)]
class ShellySwitchCommand extends Command
{
    public function __construct(
        private readonly ShellySwitchService $switchService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('device_id', InputArgument::OPTIONAL, 'Shelly device id')
            ->addArgument('shelly_command', InputArgument::OPTIONAL, 'Command name ["on", "off", "status"]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $deviceId = $input->getArgument('device_id') ?: $io->ask('Please type shelly device id');
        $action   = $input->getArgument('shelly_command') ?: $io->choice('Please select the shelly command', ["on", "off", "status"]);

        $status = $action === 'status'
            ? $this->switchService->getStatus($deviceId)
            : $this->switchService->switch($deviceId, 0, $action);

        dump(json_encode($status) ?? null);

        return Command::SUCCESS;
    }
}
