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
        $cloudAreaFraction = $data['instant']['details']['cloud_area_fraction'];
        $fogAreaFraction   = $data['instant']['details']['fog_area_fraction'];
        $sunlightFactor    = (int)max(0, 100 - ($cloudAreaFraction + $fogAreaFraction));


        return (new WeatherForecast())
            ->setTime($time)
            ->setTemperature($data['instant']['details']['air_temperature'])
            ->setTemperature6hMax($data['next_6_hours']['details']['air_temperature_max'])
            ->setTemperature6hMin($data['next_6_hours']['details']['air_temperature_min'])
            ->setPrecipitation($data['next_1_hours']['details']['precipitation_amount'])
            ->setPrecipitation6h($data['next_6_hours']['details']['precipitation_amount'])
            ->setAirPressure($data['instant']['details']['air_pressure_at_sea_level'])
            ->setClouds($cloudAreaFraction)
            ->setCloudsLow($data['instant']['details']['cloud_area_fraction_low'])
            ->setCloudsMedium($data['instant']['details']['cloud_area_fraction_medium'])
            ->setCloudsHigh($data['instant']['details']['cloud_area_fraction_high'])
            ->setHumidity($data['instant']['details']['relative_humidity'])
            ->setWindSpeed($data['instant']['details']['wind_speed'])
            ->setWindDirection($data['instant']['details']['wind_from_direction'])
            ->setFog($fogAreaFraction)
            ->setUvIndex($data['instant']['details']['ultraviolet_index_clear_sky'])
            ->setDewPointTemperature($data['instant']['details']['dew_point_temperature'])
            ->setSymbolCode($data['next_1_hours']['summary']['symbol_code'])
            ->setSymbolCode1h($data['next_1_hours']['summary']['symbol_code'])
            ->setSymbolCode6h($data['next_6_hours']['summary']['symbol_code'])
            ->setSymbolCode12h($data['next_12_hours']['summary']['symbol_code'])
            ->setSunlightFactor($sunlightFactor)
        ;
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
            ->setTemperature6hMax($newData->getTemperature6hMax())
            ->setTemperature6hMin($newData->getTemperature6hMin())
            ->setPrecipitation($newData->getPrecipitation())
            ->setPrecipitation6h($newData->getPrecipitation6h())
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
            ->setDewPointTemperature($newData->getDewPointTemperature())
            ->setSymbolCode($newData->getSymbolCode())
            ->setSymbolCode1h($newData->getSymbolCode1h())
            ->setSymbolCode6h($newData->getSymbolCode6h())
            ->setSymbolCode12h($newData->getSymbolCode12h())
            ->setSunlightFactor($newData->getSunlightFactor())
        ;
    }
}
