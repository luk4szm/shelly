<?php

declare(strict_types=1);

namespace App\Controller\Weather;

use App\Repository\AirQualityRepository;
use App\Repository\WeatherForecastRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
