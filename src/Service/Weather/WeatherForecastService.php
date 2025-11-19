<?php

namespace App\Service\Weather;

use App\Entity\WeatherForecast;
use App\Repository\WeatherForecastRepository;
use App\Service\Curl\Yrno\YrnoWeatherForecastCurlRequest;

readonly class WeatherForecastService
{
    public function __construct(
        private YrnoWeatherForecastCurlRequest $weatherForecastCurlRequest,
        private WeatherForecastRepository      $weatherForecastRepository,
    ) {
    }

    public function updateForecast(): void
    {
        foreach ($this->fetchForecast() as $item) {
            $time = (new \DateTime($item['time']))->setTimezone(new \DateTimeZone('Europe/Warsaw'));
            $data = $item['data'];

            $timeseries[] = YrnoForecastFactory::create($data, $time);
        }

        $this->storeForecast($timeseries);
    }

    private function storeForecast(array $newData): void
    {
        $oldData = $this->weatherForecastRepository->findForecast();

        /** @var WeatherForecast $newForecast */
        foreach ($newData as $newForecast) {
            $time = $newForecast->getTime();

            /** @var WeatherForecast $existingForecast */
            foreach ($oldData as $existingForecast) {
                if ($existingForecast->getTime()->getTimestamp() === $time->getTimestamp()) {
                    $timeseries[] = YrnoForecastFactory::update($newForecast, $existingForecast);

                    continue 2;
                }
            }

            $timeseries[] = $newForecast;
        }

        $this->weatherForecastRepository->save($timeseries ?? []);
    }

    private function fetchForecast(): array
    {
        $response = $this->weatherForecastCurlRequest->getForecast();

        return $response['properties']['timeseries'];
    }
}
