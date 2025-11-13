<?php

namespace App\Command;

use App\Entity\AirQuality;
use App\Repository\AirQualityRepository;
use App\Service\AirQuality\AirQualityService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:perceived:temperature',
)]
class PerceivedTemperatureTempCommand extends Command
{
    public function __construct(
        private readonly AirQualityRepository $repository,
        private readonly AirQualityService    $airQualityService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entries = $this->repository->findWithoutPerceivedTemperature();

        /** @var AirQuality $entry */
        foreach ($entries as $entry) {
            $entry->setPerceivedTemperature(
                $this->airQualityService->calculatePerceivedTemperature(
                    $entry->getTemperature(),
                    $entry->getHumidity(),
                    $entry->getMeasuredAt(),
                )
            );
        }

        $this->repository->save($entries);

        return Command::SUCCESS;
    }
}
