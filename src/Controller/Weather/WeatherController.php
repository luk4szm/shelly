<?php

declare(strict_types=1);

namespace App\Controller\Weather;

use App\Entity\AirQuality;
use App\Entity\WeatherForecast;
use App\Repository\AirQualityRepository;
use App\Repository\WeatherForecastRepository;
use App\Utils\Hook\GraphHandler\AirQualityGraphHandler;
use App\Utils\Hook\GraphHandler\WeatherForecastGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/weather', name: 'app_weather_')]
class WeatherController extends AbstractController
{
    #[Route('/daily', name: 'daily', methods: ['GET'])]
    public function daily(
        Request                   $request,
        WeatherForecastRepository $forecastRepository,
        AirQualityRepository      $airQualityRepository,
    ): Response
    {
        return $this->render('front/weather/daily.html.twig', [
            'date' => $request->get('date'),
            'airQuality' => [
                'actual' => $airQualityRepository->findLast(),
                'daily'  => $airQualityRepository->findAverageForDate(new \DateTime($request->get('date', ''))),
            ],
            'forecast' => [
                'actual' => $forecastRepository->findForecastForDate(),
                'daily'  => [],
            ],
        ]);
    }

    #[Route('/monthly', name: 'monthly', methods: ['GET'])]
    public function monthly(
        Request              $request,
        AirQualityRepository $airQualityRepository,
    ): Response
    {
        $date = new \DateTime($request->get('date', 'now'));

        return $this->render('front/weather/monthly.html.twig', [
            'date' => $date->format('Y-m-d'),
            'airQuality' => [
                'monthly'  => $airQualityRepository->findAverageForMonth($date),
            ],
        ]);
    }

    #[Route('/get-air-quality', name: 'air_quality_data', methods: ['GET'])]
    public function getAirQualityData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        return $this->json(
            array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializeAirQuality($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($request->get('date'))))
        );
    }

    #[Route('/get-air-quality-monthly-avg', name: 'air_quality_monthly_avg', methods: ['GET'])]
    public function getAirQualityMonthlyAvg(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        $dateParam = $request->get('date');
        if ($dateParam && preg_match('/^\d{4}-\d{2}$/', $dateParam)) {
            $from = new \DateTime($dateParam . '-01');
            $to   = (clone $from)->modify('last day of this month')->setTime(23, 59, 59);
            $rows = $airQualityRepository->findDailyAveragesForRange($from, $to);
        } else {
            // ostatnie 30 dni (bez parametru date)
            $to = new \DateTime('today 23:59:59');
            $from = (clone $to)->modify('-29 days')->setTime(0, 0, 0);
            $rows = $airQualityRepository->findDailyAveragesForRange($from, $to);
        }

        $out = [];
        foreach ($rows as $r) {
            $ts = (new \DateTime($r['day']))->getTimestamp() * 1000;
            $out[] = [
                'x' => $ts,
                'pm25' => $r['pm25_avg'] !== null ? (float)$r['pm25_avg'] : null,
                'pm10' => $r['pm10_avg'] !== null ? (float)$r['pm10_avg'] : null,
            ];
        }

        return $this->json($out);
    }

    #[Route('/get-atmosphere-monthly-candles', name: 'atmosphere_monthly_candles', methods: ['GET'])]
    public function getAtmosphereMonthlyCandles(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        $dateParam = $request->get('date');
        if ($dateParam && preg_match('/^\d{4}-\d{2}$/', $dateParam)) {
            $from = new \DateTime($dateParam . '-01');
            $to   = (clone $from)->modify('last day of this month')->setTime(23, 59, 59);
            $rows = $airQualityRepository->findAtmosphereCandlesForRange($from, $to);
        } else {
            // ostatnie 30 dni (bez parametru date)
            $to = new \DateTime('today 23:59:59');
            $from = (clone $to)->modify('-29 days')->setTime(0, 0, 0);
            $rows = $airQualityRepository->findAtmosphereCandlesForRange($from, $to);
        }

        $out = [
            'temperature' => [],
            'humidity' => [],
            'seaLevelPressure' => [],
        ];

        foreach ($rows as $r) {
            $ts = (new \DateTime($r['day']))->getTimestamp() * 1000;

            $out['temperature'][] = [$ts, [(float)$r['temperature_open'], (float)$r['temperature_high'], (float)$r['temperature_low'], (float)$r['temperature_close']]];
            $out['humidity'][] = [$ts, [(float)$r['humidity_open'], (float)$r['humidity_high'], (float)$r['humidity_low'], (float)$r['humidity_close']]];
            $out['seaLevelPressure'][] = [$ts, [(float)$r['seaLevelPressure_open'], (float)$r['seaLevelPressure_high'], (float)$r['seaLevelPressure_low'], (float)$r['seaLevelPressure_close']]];
        }

        return $this->json($out);
    }

    #[Route('/get-weather-data', name: 'weather_data', methods: ['GET'])]
    public function getWeatherData(
        Request                   $request,
        AirQualityRepository      $airQualityRepository,
        WeatherForecastRepository $forecastRepository,
    ): Response
    {
        $date = $request->get('date');

        $airQualityInfo = array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializeWeatherData($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($date)));

        if ($date < (new \DateTime())->format('Y-m-d')) {
            return $this->json($airQualityInfo);
        }

        $forecastData = array_map(function (WeatherForecast $weatherForecast) {
            return WeatherForecastGraphHandler::serializeForecast($weatherForecast);
        }, $forecastRepository->findForestForRestOfDay(new \DateTime($date)));

        return $this->json(array_merge($airQualityInfo, $forecastData));
    }
}
