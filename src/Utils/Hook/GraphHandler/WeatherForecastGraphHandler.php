<?php

namespace App\Utils\Hook\GraphHandler;

use App\Entity\WeatherForecast;

class WeatherForecastGraphHandler
{
    /** @param WeatherForecast[] $forecasts Ordered by forecast time ascending. */
    public static function dailyCandles(array $forecasts): array
    {
        $days = ['temperature' => [], 'seaLevelPressure' => []];
        foreach ($forecasts as $forecast) {
            $timestamp = \DateTimeImmutable::createFromInterface($forecast->getTime())
                ->setTime(0, 0)->getTimestamp() * 1000;
            foreach (['temperature' => $forecast->getTemperature(), 'seaLevelPressure' => $forecast->getAirPressure()] as $field => $value) {
                if ($value === null) {
                    continue;
                }
                if (!isset($days[$field][$timestamp])) {
                    $days[$field][$timestamp] = [$value, $value, $value, $value];
                } else {
                    $candle = &$days[$field][$timestamp];
                    $candle[1] = max($candle[1], $value);
                    $candle[2] = min($candle[2], $value);
                    $candle[3] = $value;
                    unset($candle);
                }
            }
        }

        $out = [];
        foreach ($days as $field => $candles) {
            $out[$field] = [];
            foreach ($candles as $timestamp => $values) {
                $out[$field][] = [$timestamp, $values];
            }
        }

        return $out ?? [];
    }

    public static function serializeForecast(WeatherForecast $weatherForecast): array
    {
        return [
            'measuredAt'  => $weatherForecast->getTime()->format('Y-m-d H:i:s'),
            'temperature' => (float)$weatherForecast->getTemperature(),
            'pressure'    => (float)$weatherForecast->getAirPressure(),
        ];
    }
}
