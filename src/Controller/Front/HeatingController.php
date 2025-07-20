<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Repository\HookRepository;
use App\Service\Location\LocationFinder;
use App\Utils\Hook\GraphHandler\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/heating', name: 'app_front_heating_')]
final class HeatingController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('front/heating/index.html.twig');
    }

    #[Route('/get-data/{date}', name: 'get_data')]
    public function getData(
        LocationFinder          $locationFinder,
        HookRepository          $hookRepository,
        TemperatureGraphHandler $graphHandler,
        string                  $date = '',
    ): Response {
        $from      = (new \DateTime($date))->setTime(0, 0);
        $to        = (clone $from)->modify("+1 day");
        $locations = $locationFinder->getLocations('heating');

        $currentDayHooks = $hookRepository->findLocationTemperatures(
            $from,
            $to,
            $locations,
        );

        $previousDayHooks = $hookRepository->findLocationTemperatures(
            (clone $from)->modify("-1 day"),
            (clone $to)->modify("-1 day"),
            $locations,
        );

        return $this->json([
            'currentDay'  => empty($currentDayHooks) ? [] : $graphHandler->prepareGroupedHooks($currentDayHooks),
            'previousDay' => empty($previousDayHooks) ? [] : $graphHandler->prepareGroupedHooks($previousDayHooks),
        ]);
    }
}
