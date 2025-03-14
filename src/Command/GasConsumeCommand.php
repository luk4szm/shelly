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

        $io->comment(count($indications));

        /** @var GasMeter $indication */
        foreach ($this->gasMeterRepository->findForLastMonth() as $i => $indication) {
            if (!isset($indications[$i + 1])) {
                break;
            }

            $usedEnergy = 0;
            $dateRange  = new DateRange($indications[$i + 1]->getCreatedAt(), $indication->getCreatedAt());
            $history    = $this->boilerStatusHelper->getHistory(dateRange: $dateRange);

            if ($history->isEmpty()) {
                continue;
            }

            $activeStatuses = $history->filter(function (DeviceStatus $deviceStatus) {
                return $deviceStatus->getStatus() === Status::ACTIVE;
            });

            if ($activeStatuses->isEmpty()) {
                continue;
            }

            $activeStatuses->map(function (DeviceStatus $deviceStatus) use (&$usedEnergy){
                $usedEnergy += $deviceStatus->getUsedEnergy();
            });

            dump($dateRange, $usedEnergy);
        }

        return Command::SUCCESS;
    }
}
