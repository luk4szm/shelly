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
            ->setTemperature($data['instant']['details']['air_temperature'])
            ->setPrecipitation($data['next_1_hours']['details']['precipitation_amount'])
            ->setAirPressure($data['instant']['details']['air_pressure_at_sea_level'])
            ->setClouds($data['instant']['details']['cloud_area_fraction'])
            ->setCloudsLow($data['instant']['details']['cloud_area_fraction_low'])
            ->setCloudsMedium($data['instant']['details']['cloud_area_fraction_medium'])
            ->setCloudsHigh($data['instant']['details']['cloud_area_fraction_high'])
            ->setHumidity($data['instant']['details']['relative_humidity'])
            ->setWindSpeed($data['instant']['details']['wind_speed'])
            ->setWindDirection($data['instant']['details']['wind_from_direction'])
            ->setFog($data['instant']['details']['fog_area_fraction'])
            ->setUvIndex($data['instant']['details']['ultraviolet_index_clear_sky'])
            ->setDewPointTemperature($data['instant']['details']['dew_point_temperature']);
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
            ->setPrecipitation($newData->getPrecipitation())
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
