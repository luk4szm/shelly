<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Repository\HookRepository;
use App\Repository\WeatherForecastRepository;
use App\Service\Hydration\HydrationDeviceFinder;
use App\Service\Hydration\HydrationScheduleCreator;
use App\Service\Hydration\HydrationScheduleProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/hydration', name: 'app_front_hydration_')]
final class HydrationController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        HydrationDeviceFinder     $deviceFinder,
        HydrationScheduleProvider $hydrationScheduleProvider,
        HookRepository            $hookRepository,
        WeatherForecastRepository $weatherForecastRepository,
    ): Response
    {
        return $this->render('front/hydration/index.html.twig', [
            'valves'           => $deviceFinder->getValves(),
            'hydrationPlan'    => $hydrationScheduleProvider->getPlan(),
            'hydrationHistory' => $hydrationScheduleProvider->getHistory(new \DateTime()),
            'soil'             => [
                'temp'     => $hookRepository->findActualTempForLocation('ogrod'),
                'humidity' => $hookRepository->findActualHumidityForLocation('ogrod'),
            ],
            'precipitation'    => [
                'last24h'  => $weatherForecastRepository->getSumRainfallSince(),
                'forecast' => $weatherForecastRepository->getForecastedRainfallNext24h(),
            ],
        ]);
    }

    #[Route('/save-schedule', name: 'save_schedule', methods: ['POST'])]
    public function saveSchedule(Request $request, HydrationScheduleCreator $scheduleCreator): Response
    {
        $scheduleCreator->create($request->request->all());

        $this->addFlash('success', 'Hydration schedule saved.');

        return $this->redirectToRoute('app_front_hydration_index');
    }
}
