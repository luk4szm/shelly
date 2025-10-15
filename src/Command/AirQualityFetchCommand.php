<?php

namespace App\Command;

use App\Service\AirQuality\AirQualityService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:air:quality:fetch',
    description: 'Fetch new air quality data from data sensor community',
)]
class AirQualityFetchCommand extends Command
{
    public function __construct(
        private readonly AirQualityService $airQualityService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->airQualityService->fetchData();

        return Command::SUCCESS;
    }
}
