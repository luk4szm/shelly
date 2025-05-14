<?php

namespace App\Command;

use App\Service\Shelly\Cover\ShellyCoverService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cover',
    description: 'Control shelly cover command',
)]
class ShellyCoverCommand extends Command
{
    public function __construct(
        private readonly ShellyCoverService $coverService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('shelly_command', InputArgument::OPTIONAL, 'Command name ["open", "close", "stop"]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $command = $input->getArgument('shelly_command')
            ?: $io->choice('Please select the shelly command', ["open", "close", "stop"]);

        switch ($command) {
            case 'open':
                $status = $this->coverService->open();
                break;
            case 'close':
                $status = $this->coverService->close();
                break;
            case 'stop':
                $status = $this->coverService->stop();
                break;
        }

        dump($status ?? null);

        return Command::SUCCESS;
    }
}
