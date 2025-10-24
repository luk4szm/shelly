<?php

namespace App\Service\AirQuality;

use App\Entity\AirQuality;
use App\Repository\AirQualityRepository;
use App\Service\Curl\SensorCommunity\SensorCommunityCurlRequest;
use DateTimeZone;

class AirQualityService
{
    private array $sensorData = [];

    public function __construct(
        private readonly SensorCommunityCurlRequest $sensorCommunityCurlRequest,
        private readonly AirQualityRepository       $airQualityRepository,
    ) {
    }

    public function saveData(array $data): void
    {
        $this->sensorData = $data;

        $airQuality = new AirQuality();
        $airQuality->setMeasuredAt(new \DateTime());
        $airQuality->setPm10($this->getSensorValue('SDS_P1'));
        $airQuality->setPm25($this->getSensorValue('SDS_P2'));
        $airQuality->setTemperature($this->getSensorValue('BME280_temperature'));
        $airQuality->setPressure(round($this->getSensorValue('BME280_pressure') / 100, 2));
        $airQuality->setHumidity($this->getSensorValue('BME280_humidity'));
        $airQuality->calculateSeaLevelPressure($_ENV['ALTITUDE']);

        $this->airQualityRepository->save($airQuality);
    }

    public function fetchData(): void
    {
        $response = $this->sensorCommunityCurlRequest->getAirQuality();

        if (isset($response['error']))
        {
            return;
        }

        $lastValue = $this->airQualityRepository->findLast();

        foreach ($response as $measurement) {
            $timestamp = new \DateTime($measurement['timestamp'], new DateTimeZone('UTC'));
            $timestamp->setTimezone(new DateTimeZone('Europe/Warsaw'));

            if (
                $lastValue !== null
                && $lastValue->getMeasuredAt()->format('U') >= $timestamp->format('U')
            ) {
                continue;
            }

            $airQuality = new AirQuality();
            $airQuality->setMeasuredAt($timestamp);
            $airQuality->setPm10(array_column($measurement['sensordatavalues'], "value", "value_type")['P1']);
            $airQuality->setPm25(array_column($measurement['sensordatavalues'], "value", "value_type")['P2']);
            $airQuality->setSensor($_ENV['SENSOR_COMMUNITY_ID']);

            $this->airQualityRepository->save($airQuality);
        }
    }

    /**
     * Extracts the sensor reading value ('value') for a specified value type ('value_type')
     * from the nested sensor data array, assuming the items are objects.
     *
     * @param string $valueType The type of value to search for (e.g., 'SDS_P1').
     * @return float|null The measurement value as a float, or null if not found.
     */
    private function getSensorValue(string $valueType): ?float
    {
        // Filter the array to find the element whose 'value_type' matches the requested type.
        $filtered = array_filter(
            $this->sensorData,
            fn($item) => isset($item->value_type) && $item->value_type === $valueType
        );

        // Check if any element was found
        if (empty($filtered)) {
            return null;
        }

        // Since array_column works best with arrays, we'll manually extract the 'value'
        // from the first element and cast it to float, as declared in the return type.

        // Get the first matching object
        $matchingItem = reset($filtered);

        // Check if the 'value' property exists on the object
        if (!isset($matchingItem->value)) {
            return null;
        }

        // Return the value, explicitly casting it to float
        // Note: The value in your example is a string ("22.77"), so PHP will automatically
        // convert it to float during the return, but explicit casting is clearer.
        return (float)$matchingItem->value;
    }
}
