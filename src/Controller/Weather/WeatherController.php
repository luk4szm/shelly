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

    // Miesięczne: jakości powietrza (dobowe średnie + ostatnia kolumna JS przelicza średnią kroczącą)
    #[Route('/get-air-quality-monthly-avg', name: 'air_quality_monthly_avg', methods: ['GET'])]
    public function getAirQualityMonthlyAvg(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        $date = new \DateTime($request->get('date', 'now'));
        $rows = $airQualityRepository->findDailyAveragesForMonth($date);

        // zwrot: [{ x: ts, pm25: float, pm10: float }]
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

    // Miesięczne: dane atmosferyczne (świeczki)
    #[Route('/get-atmosphere-monthly-candles', name: 'atmosphere_monthly_candles', methods: ['GET'])]
    public function getAtmosphereMonthlyCandles(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        $date = new \DateTime($request->get('date', 'now'));
        $rows = $airQualityRepository->findAtmosphereCandlesForMonth($date);

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
    public function getWeatherData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        return $this->json(
            array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializeWeather($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($request->get('date'))))
        );
    }
}
