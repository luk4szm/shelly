<?php

namespace App\Service\Weather;

use App\Entity\WeatherForecast;

class YrnoForecastFactory
{
    /**
     * @param array     $data // data structure from yr.no api service
     * @param \DateTime $time
     * @return WeatherForecast
     */
    public static function create(array $data, \DateTime $time): WeatherForecast
    {
        return (new WeatherForecast())
            ->setTime($time)
            ->setTemperature($data['air_temperature'])
            ->setAirPressure($data['air_pressure_at_sea_level'])
            ->setClouds($data['cloud_area_fraction'])
            ->setCloudsLow($data['cloud_area_fraction_low'])
            ->setCloudsMedium($data['cloud_area_fraction_medium'])
            ->setCloudsHigh($data['cloud_area_fraction_high'])
            ->setHumidity($data['relative_humidity'])
            ->setWindSpeed($data['wind_speed'])
            ->setWindDirection($data['wind_from_direction'])
            ->setFog($data['fog_area_fraction'])
            ->setUvIndex($data['ultraviolet_index_clear_sky'])
            ->setDewPointTemperature($data['dew_point_temperature']);
    }

    /**
     * @param WeatherForecast $newData
     * @param WeatherForecast $existingForecast
     * @return WeatherForecast
     */
    public static function update(WeatherForecast $newData, WeatherForecast $existingForecast): WeatherForecast
    {
        return $existingForecast
            ->setTemperature($newData->getTemperature())
            ->setAirPressure($newData->getAirPressure())
            ->setClouds($newData->getClouds())
            ->setCloudsLow($newData->getCloudsLow())
            ->setCloudsMedium($newData->getCloudsMedium())
            ->setCloudsHigh($newData->getCloudsHigh())
            ->setHumidity($newData->getHumidity())
            ->setWindSpeed($newData->getWindSpeed())
            ->setWindDirection($newData->getWindDirection())
            ->setFog($newData->getFog())
            ->setUvIndex($newData->getUvIndex())
            ->setDewPointTemperature($newData->getDewPointTemperature());
    }
}
