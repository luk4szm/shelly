<?php

namespace App\Utils\Hook\GraphHandler;

use App\Entity\WeatherForecast;

class WeatherForecastGraphHandler
{
    public static function serializeForecast(WeatherForecast $weatherForecast): array
    {
        return [
            'measuredAt'  => $weatherForecast->getTime()->format('Y-m-d H:i:s'),
            'temperature' => (float)$weatherForecast->getTemperature(),
        ];
    }
}
