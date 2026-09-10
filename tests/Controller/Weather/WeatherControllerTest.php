<?php

declare(strict_types=1);

namespace App\Tests\Controller\Weather;

use App\Controller\Weather\WeatherController;
use App\Entity\WeatherForecast;
use App\Repository\AirQualityRepository;
use App\Repository\WeatherForecastRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

final class WeatherControllerTest extends TestCase
{
    /** @dataProvider monthProvider */
    public function testMonthlyForecastWindow(?string $month, bool $includeForecast): void
    {
        $today = new \DateTimeImmutable('today');
        $from = $today->modify('+1 day');
        $until = $today->modify('+4 days');
        if ($month !== null) {
            $until = min($until, new \DateTimeImmutable($month . '-01 +1 month'));
        }
        $includeForecast = $includeForecast && $from < $until;

        $airQuality = $this->createMock(AirQualityRepository::class);
        $airQuality->method('findAtmosphereDailyCandlesForRange')->willReturn([]);
        $forecast = $this->createMock(WeatherForecastRepository::class);
        if ($includeForecast) {
            $forecast->expects(self::once())->method('findForRange')->with($from, $until)->willReturn([
                (new WeatherForecast())->setTime($from)->setTemperature(12)->setAirPressure(1005),
            ]);
        } else {
            $forecast->expects(self::never())->method('findForRange');
        }

        $controller = new WeatherController();
        $controller->setContainer(new Container());
        $response = $controller->getAtmosphereMonthlyCandles(
            new Request($month === null ? [] : ['date' => $month]), $airQuality, $forecast
        );
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([], $data['temperature']);
        self::assertSame([], $data['humidity']);
        self::assertCount($includeForecast ? 1 : 0, $data['forecast']['temperature']);
        self::assertCount($includeForecast ? 1 : 0, $data['forecast']['seaLevelPressure']);
    }

    public function monthProvider(): iterable
    {
        $month = new \DateTimeImmutable('first day of this month');
        yield 'last 30 days' => [null, true];
        yield 'current month' => [$month->format('Y-m'), true];
        yield 'past month' => [$month->modify('-1 month')->format('Y-m'), false];
        yield 'future month' => [$month->modify('+1 month')->format('Y-m'), false];
    }
}
