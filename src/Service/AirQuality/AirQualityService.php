<?php

namespace App\Service\AirQuality;

use App\Entity\AirQuality;
use App\Repository\AirQualityRepository;
use App\Service\Curl\SensorCommunity\SensorCommunityCurlRequest;
use DateTimeZone;

class AirQualityService
{
    public function __construct(
        private SensorCommunityCurlRequest $sensorCommunityCurlRequest,
        private AirQualityRepository       $airQualityRepository,
    ) {
    }

    public function fetchData(): void
    {
        $response = $this->sensorCommunityCurlRequest->getAirQuality();

        if (isset($response['error']))
        {
            return;
        }

        $lastValue = $this->airQualityRepository->findLast();

        foreach ($response as $measurement) {
            $timestamp = new \DateTime($measurement['timestamp'], new DateTimeZone('UTC'));
            $timestamp->setTimezone(new DateTimeZone('Europe/Warsaw'));

            if (
                $lastValue !== null
                && $lastValue->getMeasuredAt()->format('U') <= $timestamp->format('U')
            ) {
                continue;
            }

            $airQuality = new AirQuality();
            $airQuality->setMeasuredAt($timestamp);
            $airQuality->setPm10(array_column($measurement['sensordatavalues'], "value", "value_type")['P1']);
            $airQuality->setPm25(array_column($measurement['sensordatavalues'], "value", "value_type")['P2']);
            $airQuality->setSensor($_ENV['SENSOR_COMMUNITY_ID']);

            $this->airQualityRepository->save($airQuality);
        }
    }
}
