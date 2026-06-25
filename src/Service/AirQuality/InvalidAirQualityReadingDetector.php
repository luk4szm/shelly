<?php

declare(strict_types=1);

namespace App\Service\AirQuality;

use App\Entity\AirQuality;

final class InvalidAirQualityReadingDetector
{
    private const FIELD_CONFIG = [
        'temperature' => [
            'getter'          => 'getTemperature',
            'label'           => 'Temperatura',
            'unit'            => '°C',
            'decimals'        => 2,
            'absoluteMin'     => -35.0,
            'absoluteMax'     => 55.0,
            'jumpThreshold'   => 3.0,
            'bridgeTolerance' => 1.2,
        ],
        'pressure'    => [
            'getter'          => 'getSeaLevelPressure',
            'label'           => 'Ciśnienie',
            'unit'            => 'hPa',
            'decimals'        => 1,
            'absoluteMin'     => 930.0,
            'absoluteMax'     => 1070.0,
            'jumpThreshold'   => 2.5,
            'bridgeTolerance' => 1.0,
        ],
        'humidity'    => [
            'getter'          => 'getHumidity',
            'label'           => 'Wilgotność',
            'unit'            => '%',
            'decimals'        => 1,
            'absoluteMin'     => 0.0,
            'absoluteMax'     => 100.0,
            'jumpThreshold'   => 12.0,
            'bridgeTolerance' => 6.0,
        ],
        'pm25'        => [
            'getter'          => 'getPm25',
            'label'           => 'PM2.5',
            'unit'            => 'µg/m³',
            'decimals'        => 2,
            'absoluteMin'     => 0.0,
            'absoluteMax'     => 400.0,
            'jumpThreshold'   => 35.0,
            'bridgeTolerance' => 15.0,
        ],
        'pm10'        => [
            'getter'          => 'getPm10',
            'label'           => 'PM10',
            'unit'            => 'µg/m³',
            'decimals'        => 2,
            'absoluteMin'     => 0.0,
            'absoluteMax'     => 600.0,
            'jumpThreshold'   => 50.0,
            'bridgeTolerance' => 20.0,
        ],
    ];

    public function getFieldChoices(): array
    {
        $choices = [];

        foreach (self::FIELD_CONFIG as $key => $config) {
            $choices[] = [
                'id'    => $key,
                'label' => $config['label'],
                'unit'  => $config['unit'],
            ];
        }

        return $choices;
    }

    public function getFieldMetadata(string $field): array
    {
        return $this->getConfig($field);
    }

    /**
     * @param AirQuality[] $dayReadings
     */
    public function preview(
        array              $dayReadings,
        string             $field,
        \DateTimeInterface $selectedFrom,
        \DateTimeInterface $selectedTo,
    ): array {
        $config = $this->getConfig($field);
        $items  = $this->buildItems($dayReadings, $config);

        if ($items === []) {
            return [];
        }

        $candidates = [];

        foreach ($items as $item) {
            if (!$this->isInsideSelectedRange($item['measuredAt'], $selectedFrom, $selectedTo)) {
                continue;
            }

            if ($item['value'] < $config['absoluteMin'] || $item['value'] > $config['absoluteMax']) {
                $this->addCandidate(
                    $candidates,
                    $item,
                    sprintf(
                        'Wartość %.' . $config['decimals'] . 'f %s jest poza dopuszczalnym zakresem %.' . $config['decimals'] . 'f - %.' . $config['decimals'] . 'f %s.',
                        $item['value'],
                        $config['unit'],
                        $config['absoluteMin'],
                        $config['absoluteMax'],
                        $config['unit'],
                    )
                );
            }
        }

        foreach ($this->detectBridgeSegments($items, $config) as $segment) {
            for ($i = $segment['start']; $i <= $segment['end']; ++$i) {
                $item = $items[$i];

                if (!$this->isInsideSelectedRange($item['measuredAt'], $selectedFrom, $selectedTo)) {
                    continue;
                }

                $this->addCandidate($candidates, $item, $segment['reason']);
            }
        }

        foreach ($this->detectNeighborSpikes($items, $config) as $index => $reason) {
            $item = $items[$index];

            if (!$this->isInsideSelectedRange($item['measuredAt'], $selectedFrom, $selectedTo)) {
                continue;
            }

            $this->addCandidate($candidates, $item, $reason);
        }

        uasort($candidates, static fn(array $left, array $right): int => $left['measuredAtObject'] <=> $right['measuredAtObject']);

        return array_map(function (array $candidate) use ($config) {
            return [
                'id'         => $candidate['id'],
                'measuredAt' => $candidate['measuredAtObject']->format('Y-m-d H:i:s'),
                'value'      => round($candidate['value'], $config['decimals']),
                'reasons'    => array_values($candidate['reasons']),
            ];
        }, array_values($candidates));
    }

    public function nullifyField(AirQuality $reading, string $field): void
    {
        $this->getConfig($field);

        switch ($field) {
            case 'temperature':
                $reading->setTemperature(null);
                $reading->setPerceivedTemperature(null);
                break;

            case 'pressure':
                $reading->setPressure(null);
                $reading->setSeaLevelPressure(null);
                break;

            case 'humidity':
                $reading->setHumidity(null);
                $reading->setPerceivedTemperature(null);
                break;

            case 'pm25':
                $reading->setPm25(null);
                break;

            case 'pm10':
                $reading->setPm10(null);
                break;
        }
    }

    private function getConfig(string $field): array
    {
        if (!isset(self::FIELD_CONFIG[$field])) {
            throw new \InvalidArgumentException(sprintf('Unsupported air quality field "%s".', $field));
        }

        return self::FIELD_CONFIG[$field];
    }

    /**
     * @param AirQuality[] $dayReadings
     *
     * @return array<int, array{
     *     cacheKey: int,
     *     id: ?int,
     *     measuredAt: \DateTimeInterface,
     *     value: float
     * }>
     */
    private function buildItems(array $dayReadings, array $config): array
    {
        usort($dayReadings, static fn(AirQuality $left, AirQuality $right): int => $left->getMeasuredAt() <=> $right->getMeasuredAt());

        $items  = [];
        $getter = $config['getter'];

        foreach ($dayReadings as $reading) {
            $value = $reading->{$getter}();

            if ($value === null || $reading->getMeasuredAt() === null) {
                continue;
            }

            $items[] = [
                'cacheKey'   => spl_object_id($reading),
                'id'         => $reading->getId(),
                'measuredAt' => $reading->getMeasuredAt(),
                'value'      => (float)$value,
            ];
        }

        return $items;
    }

    private function isInsideSelectedRange(
        \DateTimeInterface $measuredAt,
        \DateTimeInterface $selectedFrom,
        \DateTimeInterface $selectedTo,
    ): bool {
        return $measuredAt >= $selectedFrom && $measuredAt <= $selectedTo;
    }

    private function addCandidate(array &$candidates, array $item, string $reason): void
    {
        if (!isset($candidates[$item['cacheKey']])) {
            $candidates[$item['cacheKey']] = [
                'id'               => $item['id'],
                'measuredAtObject' => $item['measuredAt'],
                'value'            => $item['value'],
                'reasons'          => [],
            ];
        }

        $candidates[$item['cacheKey']]['reasons'][$reason] = $reason;
    }

    /**
     * Szuka "wysp" odczytów: nagły skok, seria nietypowych wartości i powrót
     * do wcześniejszego poziomu.
     *
     * @return array<int, array{start: int, end: int, reason: string}>
     */
    private function detectBridgeSegments(array $items, array $config): array
    {
        $segments = [];
        $count    = count($items);

        for ($start = 1; $start < $count - 1; ++$start) {
            $jumpIn = abs($items[$start]['value'] - $items[$start - 1]['value']);

            if ($jumpIn < $config['jumpThreshold']) {
                continue;
            }

            $baseline = $items[$start - 1]['value'];

            for ($endJump = $start + 1; $endJump < $count; ++$endJump) {
                $jumpOut = abs($items[$endJump]['value'] - $items[$endJump - 1]['value']);

                if ($jumpOut < $config['jumpThreshold']) {
                    continue;
                }

                $returnLevel = $items[$endJump]['value'];

                if (abs($baseline - $returnLevel) > $config['bridgeTolerance']) {
                    continue;
                }

                $segmentValues = array_column(array_slice($items, $start, $endJump - $start), 'value');

                if ($segmentValues === []) {
                    continue;
                }

                $segmentMedian = $this->median($segmentValues);

                if (abs($segmentMedian - $baseline) < $config['jumpThreshold']) {
                    continue;
                }

                $segments[] = [
                    'start'  => $start,
                    'end'    => $endJump - 1,
                    'reason' => $endJump - $start === 1
                        ? 'Pojedynczy odczyt tworzy skok niemożliwy do uzyskania w 150 sekund.'
                        : 'Seria odczytów po nagłym skoku wraca do wcześniejszego poziomu.',
                ];

                $start = $endJump;
                continue 2;
            }
        }

        return $segments;
    }

    /**
     * @return array<int, string>
     */
    private function detectNeighborSpikes(array $items, array $config): array
    {
        $reasons = [];
        $count   = count($items);

        for ($i = 1; $i < $count - 1; ++$i) {
            $previous = $items[$i - 1]['value'];
            $current  = $items[$i]['value'];
            $next     = $items[$i + 1]['value'];

            if (
                abs($current - $previous) >= $config['jumpThreshold']
                && abs($current - $next) >= $config['jumpThreshold']
                && abs($previous - $next) <= $config['bridgeTolerance']
            ) {
                $reasons[$i] = 'Odczyt gwałtownie odbiega od obu sąsiednich pomiarów.';
            }
        }

        return $reasons;
    }

    /**
     * @param float[] $values
     */
    private function median(array $values): float
    {
        sort($values, SORT_NUMERIC);

        $count  = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
