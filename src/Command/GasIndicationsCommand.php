<?php

namespace App\Command;

use App\Repository\GasMeterRepository;
use App\Service\GasMeter\GasIndicationDailyStatsCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:gas:indications',
    description: 'List of gas meter indications with certain data',
)]
class GasIndicationsCommand extends Command
{
    public function __construct(
        private readonly GasIndicationDailyStatsCalculator $indicationDailyStats,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::OPTIONAL, 'Date why you want to see statistics')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $date = $input->getArgument('date')
            ? new \DateTime($input->getArgument('date'))
            : (new \DateTime())->setTime(0, 0);

        $dailyConsumption = $this->indicationDailyStats->getDailyConsumption($date);

        dd($dailyConsumption);

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
