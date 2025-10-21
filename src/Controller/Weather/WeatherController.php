<?php

declare(strict_types=1);

namespace App\Controller\Weather;

use App\Entity\AirQuality;
use App\Repository\AirQualityRepository;
use App\Repository\WeatherForecastRepository;
use App\Utils\Hook\GraphHandler\AirQualityGraphHandler;
use App\Utils\Hook\GraphHandler\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WeatherController extends AbstractController
{
    #[Route('/weather', name: 'app_weather_index', methods: ['GET'])]
    public function index(WeatherForecastRepository $forecastRepository, AirQualityRepository $airQualityRepository): Response
    {
        return $this->render('front/weather/index.html.twig', [
            'airQuality' => [
                'actual' => $airQualityRepository->findLast(),
                'daily'  => [],
            ],
            'forecast' => [
                'actual' => $forecastRepository->findActualForecast(),
                'daily'  => [],
            ],
        ]);
    }

    #[Route('/weather/get-air-quality', name: 'app_weather_air_quality_data', methods: ['GET'])]
    public function getAirQualityData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        return $this->json(
            array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializeAirQuality($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($request->get('date'))))
        );
    }

    #[Route('/weather/get-weather-data', name: 'app_weather_data', methods: ['GET'])]
    public function getWeatherData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        return $this->json(
            array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializeWeather($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($request->get('date'))))
        );
    }

    #[Route('/weather/get-temperature', name: 'app_weather_temperature_data', methods: ['GET'])]
    public function getTemperatureData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        return $this->json(
            array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializeTemperature($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($request->get('date'))))
        );
    }

    #[Route('/weather/get-pressure', name: 'app_weather_pressure_data', methods: ['GET'])]
    public function getPressureData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        return $this->json(
            array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializePressure($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($request->get('date'))))
        );
    }

    #[Route('/weather/get-humidity', name: 'app_weather_humidity_data', methods: ['GET'])]
    public function getHumidityData(Request $request, AirQualityRepository $airQualityRepository): Response
    {
        return $this->json(
            array_map(function (AirQuality $airQuality) {
                return AirQualityGraphHandler::serializeHumidity($airQuality);
            }, $airQualityRepository->findForDate(new \DateTime($request->get('date'))))
        );
    }
}
