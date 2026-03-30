<?php

namespace App\Service\AirQuality;

use App\Repository\AirQualityRepository;

readonly class InsolationService
{
    public function __construct(
        private AirQualityRepository $repository,
    ) {}

    public function store(float $value): void
    {
        $lastValue     = $this->repository->findLastInsolationReading();
        $entriesToFill = $this->repository->findToFillWithInsolation();

        foreach ($entriesToFill as $key => $airQuality) {
            $airQuality->setInsolation($key === array_key_last($entriesToFill) ? $value : $lastValue);
        }

        $this->repository->save($entriesToFill);
    }
}
