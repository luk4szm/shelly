<?php

namespace App\Command;

use App\Service\Gate\SuplaGateOpener;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:gate',
    description: 'Add a short description for your command',
)]
class SuplaGateCommand extends Command
{
    public function __construct(private readonly SuplaGateOpener $gateOpener)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('suplaCommand', InputArgument::OPTIONAL, 'Command name ["read", "open-close]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $command = $input->getArgument('suplaCommand')
            ?: $io->choice('Please select the supla command', ["read", "open-close"], "read");

        switch ($command) {
            case 'read':
                $status = $this->gateOpener->read();
                break;
            case 'open-close':
                $status = $this->gateOpener->open();
                break;
        }

        dump($status ?? null);

        return Command::SUCCESS;
    }
}
