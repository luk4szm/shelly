<?php

declare(strict_types=1);

namespace App\Controller\Weather;

use App\Entity\AirQuality;
use App\Repository\AirQualityRepository;
use App\Repository\WeatherForecastRepository;
use App\Utils\Hook\GraphHandler\AirQualityGraphHandler;
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
                'actual' => $forecastRepository->findActualForecast(),
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

    #[Route('/get-air-quality-monthly', name: 'air_quality_monthly_data', methods: ['GET'])]
    public function getAirQualityMonthlyData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        $date = new \DateTime($request->get('date', 'now'));
        $rows = $airQualityRepository->findCandleDataForMonth($date);

        // Serialization in the format for candlestick charts (ApexCharts: [x, [open, high, low, close]])
        $out = [
            'pm25'             => [],
            'pm10'             => [],
            'temperature'      => [],
            'humidity'         => [],
            'seaLevelPressure' => [],
        ];

        foreach ($rows as $r) {
            $ts = (new \DateTime($r['day']))->getTimestamp() * 1000;

            $out['pm25'][] = [$ts, [(float)$r['pm25_open'], (float)$r['pm25_high'], (float)$r['pm25_low'], (float)$r['pm25_close']]];
            $out['pm10'][] = [$ts, [(float)$r['pm10_open'], (float)$r['pm10_high'], (float)$r['pm10_low'], (float)$r['pm10_close']]];

            $out['temperature'][]      = [$ts, [(float)$r['temperature_open'], (float)$r['temperature_high'], (float)$r['temperature_low'], (float)$r['temperature_close']]];
            $out['humidity'][]         = [$ts, [(float)$r['humidity_open'], (float)$r['humidity_high'], (float)$r['humidity_low'], (float)$r['humidity_close']]];
            $out['seaLevelPressure'][] = [$ts, [(float)$r['seaLevelPressure_open'], (float)$r['seaLevelPressure_high'], (float)$r['seaLevelPressure_low'], (float)$r['seaLevelPressure_close']]];
        }

        return $this->json($out);
    }

    #[Route('/get-weather-data', name: 'weather_data', methods: ['GET'])]
    public function getWeatherData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        return $this->json(
            array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializeWeather($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($request->get('date'))))
        );
    }
}
