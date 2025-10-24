<?php

namespace App\Command;

use App\Entity\AirQuality;
use App\Repository\AirQualityRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:air:quality:tmp',
)]
class AirQualityTmpCommand extends Command
{
    public function __construct(
        private readonly AirQualityRepository $repository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var AirQuality $airQuality */
        foreach ($this->repository->findAll() as $airQuality) {
            if ($airQuality->getSeaLevelPressure() === null) {
                $airQuality->calculateSeaLevelPressure($_ENV['ALTITUDE']);
                $this->repository->save($airQuality, false);
            }
        }

        $this->repository->save($airQuality);

        return Command::SUCCESS;
    }
}
