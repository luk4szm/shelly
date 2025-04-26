<?php

namespace App\Service\Curl\Yrno;

use App\Service\Curl\Curl;

class YrnoWeatherForecastCurlRequest extends Curl
{
    private const URL    = 'htt1ps://api.met.no/weatherapi/locationforecast/2.0/complete?lat=52.403&lon=16.670'; // Sierosław
    private const METHOD = 'GET';

    public function getForecast(): array
    {
        return $this->request(self::METHOD, self::URL);
    }
}
