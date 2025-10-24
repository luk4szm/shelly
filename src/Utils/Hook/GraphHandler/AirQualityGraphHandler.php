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

    public static function serializeWeather(AirQuality $airQuality): array
    {
        return [
            'measuredAt'  => $airQuality->getMeasuredAt()->format('Y-m-d H:i:s'),
            'temperature' => (float)$airQuality->getTemperature(),
            'humidity'    => (float)$airQuality->getHumidity(),
            'pressure'    => (float)$airQuality->getSeaLevelPressure(),
        ];
    }

    public static function serializeTemperature(AirQuality $airQuality): array
    {
        return [
            'measuredAt' => $airQuality->getMeasuredAt()->format('Y-m-d H:i:s'),
            'value'      => (float)$airQuality->getTemperature(),
        ];
    }

    public static function serializeHumidity(AirQuality $airQuality): array
    {
        return [
            'measuredAt' => $airQuality->getMeasuredAt()->format('Y-m-d H:i:s'),
            'value'      => (float)$airQuality->getHumidity(),
        ];
    }

    public static function serializePressure(AirQuality $airQuality): array
    {
        return [
            'measuredAt' => $airQuality->getMeasuredAt()->format('Y-m-d H:i:s'),
            'value'      => (float)$airQuality->getPressure(),
        ];
    }
}
