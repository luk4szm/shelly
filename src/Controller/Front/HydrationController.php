<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Repository\HookRepository;
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
    ): Response
    {
        return $this->render('front/hydration/index.html.twig', [
            'valves'        => $deviceFinder->getValves(),
            'hydrationPlan' => $hydrationScheduleProvider->getPlan(),
            'soil'         => [
                'temp'     => $hookRepository->findActualTempForLocation('ogrod'),
                'humidity' => $hookRepository->findActualHumidityForLocation('ogrod'),
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
