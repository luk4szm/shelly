<?php

namespace App\Utils\Hook\GraphHandler;

use App\Entity\AirQuality;

class AirQualityGraphHandler
{
    public static function serializeAirQuality(AirQuality $airQuality): array
    {
        $toFloat = fn($value) => $value !== null ? (float)$value : null;

        return [
            'measuredAt' => $airQuality->getMeasuredAt()->format('Y-m-d H:i:s'),
            'pm25'       => $toFloat($airQuality->getPm25()),
            'pm10'       => $toFloat($airQuality->getPm10()),
        ];
    }

    public static function serializeWeatherData(AirQuality $airQuality): array
    {
        $toFloat = fn($value) => $value !== null ? (float)$value : null;

        return [
            'measuredAt'           => $airQuality->getMeasuredAt()->format('Y-m-d H:i:s'),
            'temperature'          => $toFloat($airQuality->getTemperature()),
            'perceivedTemperature' => $toFloat($airQuality->getPerceivedTemperature()),
            'humidity'             => $toFloat($airQuality->getHumidity()),
            'pressure'             => $toFloat($airQuality->getSeaLevelPressure()),
        ];
    }
}
