<?php

namespace App\Service\Curl\SensorCommunity;

use App\Service\Curl\Curl;

class SensorCommunityCurlRequest extends Curl
{
    private const URL    = 'https://data.sensor.community/airrohr/v1/sensor/%d/';
    private const METHOD = 'GET';

    public function getAirQuality(): array
    {
        return $this->request(
            self::METHOD,
            sprintf(self::URL, $_ENV['SENSOR_COMMUNITY_ID']),
        );
    }
}
