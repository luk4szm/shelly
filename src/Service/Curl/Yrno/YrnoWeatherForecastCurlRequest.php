<?php

namespace App\Service\Curl\Yrno;

use App\Service\Curl\Curl;

class YrnoWeatherForecastCurlRequest extends Curl
{
    private const URL    = 'https://api.met.no/weatherapi/locationforecast/2.0/complete?lat=%s&lon=%s'; // Sierosław
    private const METHOD = 'GET';

    public function getForecast(): array
    {
        return $this->request(
            self::METHOD,
            sprintf(self::URL, $_ENV['LATITUDE'], $_ENV['LONGITUDE']),
        );
    }
}
