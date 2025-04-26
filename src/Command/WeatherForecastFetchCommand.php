<?php

namespace App\Command;

use App\Service\Weather\WeatherForecastService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:weather:forecast:fetch',
    description: 'Fetch new weather forecast from yr.no api',
)]
class WeatherForecastFetchCommand extends Command
{
    public function __construct(
        private readonly WeatherForecastService $forecastService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->forecastService->updateForecast();

        return Command::SUCCESS;
    }
}
