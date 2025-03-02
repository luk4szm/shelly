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

        $io->title(sprintf(
            '[%s] Retrieving the statistics of the day (%s) for gas consume',
            (new \DateTime())->format('H:i:s'),
            $date->format('Y-m-d'),
        ));

        $dailyConsumption = $this->indicationDailyStats->getDailyConsumption($date);

        $output->writeln([
            sprintf('Total fuel consume: <info>%.2f m3</info>', $dailyConsumption->getConsumption()),
            sprintf('Total used energy: <info>%.1f Wh</info>', $dailyConsumption->getEnergyUsed()),
            sprintf('Estimated cost: <info>%.2f zł</info>', $dailyConsumption->getEstimatedCost()),
            sprintf('Total active time: <info>%s</info>', $dailyConsumption->getBoilerActiveTimeReadable()),
            sprintf('Number of active cycles: <info>%d</info>', $dailyConsumption->getBoilerInclusions()),
            sprintf('Average runtime: <info>%s</info>', $dailyConsumption->getBoilerAverageRuntimeReadable()),
            sprintf('Average fuel consume per runtime: <info>%.3f m3</info>', $dailyConsumption->getAverageFuelConsumePerRuntime()),
            sprintf('Estimated cost: <info>%.2f zł</info>', $dailyConsumption->getEstimatedCost()),
            '',
        ]);

        return Command::SUCCESS;
    }
}
