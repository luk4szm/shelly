<?php

declare(strict_types=1);

namespace App\Tests\Service\AirQuality;

use App\Entity\AirQuality;
use App\Service\AirQuality\InvalidAirQualityReadingDetector;
use PHPUnit\Framework\TestCase;

final class InvalidAirQualityReadingDetectorTest extends TestCase
{
    private InvalidAirQualityReadingDetector $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new InvalidAirQualityReadingDetector();
    }

    public function testDetectsSinglePressureSpike(): void
    {
        $readings = [
            $this->createReading('2026-06-24 00:00:00', pressure: 1009.0),
            $this->createReading('2026-06-24 00:02:30', pressure: 1009.1),
            $this->createReading('2026-06-24 00:05:00', pressure: 1024.9),
            $this->createReading('2026-06-24 00:07:30', pressure: 1009.0),
            $this->createReading('2026-06-24 00:10:00', pressure: 1008.9),
        ];

        $candidates = $this->sut->preview(
            $readings,
            'pressure',
            new \DateTimeImmutable('2026-06-24 00:00:00'),
            new \DateTimeImmutable('2026-06-24 23:59:59'),
        );

        self::assertCount(1, $candidates);
        self::assertSame('2026-06-24 00:05:00', $candidates[0]['measuredAt']);
    }

    public function testDetectsPressureIslandThatReturnsToBaseline(): void
    {
        $readings = [
            $this->createReading('2026-06-24 10:00:00', pressure: 1008.9),
            $this->createReading('2026-06-24 10:02:30', pressure: 1008.8),
            $this->createReading('2026-06-24 10:05:00', pressure: 1020.5),
            $this->createReading('2026-06-24 10:07:30', pressure: 1020.7),
            $this->createReading('2026-06-24 10:10:00', pressure: 1020.6),
            $this->createReading('2026-06-24 10:12:30', pressure: 1008.9),
        ];

        $candidates = $this->sut->preview(
            $readings,
            'pressure',
            new \DateTimeImmutable('2026-06-24 00:00:00'),
            new \DateTimeImmutable('2026-06-24 23:59:59'),
        );

        self::assertCount(3, $candidates);
        self::assertSame(
            ['2026-06-24 10:05:00', '2026-06-24 10:07:30', '2026-06-24 10:10:00'],
            array_column($candidates, 'measuredAt')
        );
    }

    public function testDetectsHumidityOutOfPhysicalRange(): void
    {
        $readings = [
            $this->createReading('2026-06-24 08:00:00', humidity: 48.0),
            $this->createReading('2026-06-24 08:02:30', humidity: 135.0),
            $this->createReading('2026-06-24 08:05:00', humidity: 47.0),
        ];

        $candidates = $this->sut->preview(
            $readings,
            'humidity',
            new \DateTimeImmutable('2026-06-24 00:00:00'),
            new \DateTimeImmutable('2026-06-24 23:59:59'),
        );

        self::assertCount(1, $candidates);
        self::assertSame('2026-06-24 08:02:30', $candidates[0]['measuredAt']);
        self::assertNotEmpty($candidates[0]['reasons']);
    }

    public function testNullifyingTemperatureAlsoClearsPerceivedTemperature(): void
    {
        $reading = $this->createReading('2026-06-24 12:00:00', temperature: 36.5);
        $reading->setPerceivedTemperature(40.1);

        $this->sut->nullifyField($reading, 'temperature');

        self::assertNull($reading->getTemperature());
        self::assertNull($reading->getPerceivedTemperature());
    }

    private function createReading(
        string $measuredAt,
        ?float $pressure = null,
        ?float $temperature = null,
        ?float $humidity = null,
        ?float $pm25 = null,
        ?float $pm10 = null,
    ): AirQuality
    {
        $reading = new AirQuality();
        $reading->setMeasuredAt(new \DateTime($measuredAt));
        $reading->setSeaLevelPressure($pressure);
        $reading->setTemperature($temperature);
        $reading->setHumidity($humidity);
        $reading->setPm25($pm25);
        $reading->setPm10($pm10);

        return $reading;
    }
}
