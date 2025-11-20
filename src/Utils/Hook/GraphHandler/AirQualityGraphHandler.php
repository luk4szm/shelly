<?php

namespace App\Utils\Hook\GraphHandler;

use App\Entity\AirQuality;

class AirQualityGraphHandler
{
    public static function serializeAirQuality(AirQuality $airQuality): array
    {
        return [
            'measuredAt' => $airQuality->getMeasuredAt()->format('Y-m-d H:i:s'),
            'pm25'       => (float)$airQuality->getPm25(),
            'pm10'       => (float)$airQuality->getPm10(),
        ];
    }

    public static function serializeWeatherData(AirQuality $airQuality): array
    {
        return [
            'measuredAt'           => $airQuality->getMeasuredAt()->format('Y-m-d H:i:s'),
            'temperature'          => (float)$airQuality->getTemperature(),
            'perceivedTemperature' => (float)$airQuality->getPerceivedTemperature(),
            'humidity'             => (float)$airQuality->getHumidity(),
            'pressure'             => (float)$airQuality->getSeaLevelPressure(),
        ];
    }
}
