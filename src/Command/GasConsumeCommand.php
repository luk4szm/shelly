<?php

namespace App\Command;

use App\Entity\GasMeter;
use App\Model\DateRange;
use App\Model\DeviceStatus;
use App\Model\Status;
use App\Repository\GasMeterRepository;
use App\Service\DeviceStatus\BoilerDeviceStatusHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:gas:consume',
    description: 'Calculate consume o gas by hooks',
)]
class GasConsumeCommand extends Command
{
    public function __construct(
        private readonly BoilerDeviceStatusHelper $boilerStatusHelper,
        private readonly GasMeterRepository $gasMeterRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $indications = $this->gasMeterRepository->findForLastMonth();

        /** @var GasMeter $indication */
        foreach ($indications as $i => $indication) {
            if (!isset($indications[$i + 1])) {
                break;
            }

            $usedEnergy  = 0;
            $runtime     = 0;
            $dateRange   = new DateRange($indications[$i + 1]->getCreatedAt(), $indication->getCreatedAt());
            $gasConsumed = $indication->getIndication() - $indications[$i + 1]->getIndication();
            $history     = $this->boilerStatusHelper->getHistory(dateRange: $dateRange);

            if ($history === null || $history->isEmpty()) {
                continue;
            }

            $activeStatuses = $history->filter(function (DeviceStatus $deviceStatus) {
                return $deviceStatus->getStatus() === Status::ACTIVE;
            });

            if ($activeStatuses->isEmpty()) {
                continue;
            }

            $activeStatuses->map(function (DeviceStatus $deviceStatus) use (&$usedEnergy, &$runtime){
                $usedEnergy += $deviceStatus->getUsedEnergy();
                $runtime    += $deviceStatus->getStatusDuration();
            });

            $io->writeln([
                sprintf('Date range %s - %s', $dateRange->getFrom()->format('Y-m-d H:i'), $dateRange->getTo()->format('Y-m-d H:i')),
                sprintf('Active runtimes: %d', $activeStatuses->count()),
                sprintf('Gas consumed per runtime: %.3f m3', $gasConsumed / $activeStatuses->count()),
                sprintf('Gas consumed per Wh: %.3f m3', $gasConsumed / $usedEnergy),
                sprintf('Gas consumed active hour: %.3f m3', $gasConsumed / ($runtime / 3600)),
                ''
            ]);
        }

        return Command::SUCCESS;
    }
}
