<?php

declare(strict_types=1);

namespace App\Controller\Front\Location;

use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Utils\Hook\GraphHandler\HumidityGraphHandler;
use App\Utils\Hook\GraphHandler\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location/{location}', name: 'app_front_default_location_')]
class LocationController extends AbstractController
{
    #[Route('/daily', name: 'daily')]
    public function daily(string $location, HookRepository $hookRepository): Response
    {
        $current = [
            'temperature' => $hookRepository->findActualTempForLocation($location),
            'humidity'    => $hookRepository->findActualHumidityForLocation($location),
        ];

        return $this->render('front/location/default/daily.html.twig', [
            'location' => $location,
            'current'  => $current,
        ]);
    }

    #[Route('/monthly', name: 'monthly')]
    public function monthly(string $location): Response
    {
        return $this->render('front/location/default/monthly.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/get-daily-data', name: 'get_daily_data')]
    public function getDailyData(
        Request        $request,
        HookRepository $hookRepository,
        string         $location,
    ): Response {
        $date = $request->query->get('date', '');
        $from = (new \DateTime($date))->setTime(0, 0);
        $to   = (new \DateTime($date))->setTime(23, 59, 59);

        return $this->json([
            'temperature' => array_map(function (Hook $hook) {
                return TemperatureGraphHandler::serialize($hook);
            }, $hookRepository->findLocationTemperatures($from, $to, $location)),
            'humidity'    => array_map(function (Hook $hook) {
                return HumidityGraphHandler::serialize($hook);
            }, $hookRepository->findLocationHumidity($from, $to, $location)),
        ]);
    }

    #[Route('/get-monthly-data', name: 'get_monthly_data')]
    public function getMonthlyData(
        Request        $request,
        HookRepository $hookRepository,
        string         $location,
    ): Response {
        $date = $request->query->get('date', '');
        $type = $request->query->get('type');

        if ($date === 'last30days' || empty($date)) {
            // Zakres: ostatnie 30 dni (od dzisiaj - 30 dni do dzisiaj)
            $to   = (new \DateTime())->setTime(23, 59, 59);
            $from = (new \DateTime())->modify('-30 days')->setTime(0, 0);
        } else {
            // Zakres: konkretny miesiąc
            $from = (new \DateTime($date))->modify('first day of this month')->setTime(0, 0);
            $to   = (new \DateTime($date))->modify('last day of this month')->setTime(23, 59, 59);
        }

        return $this->json($hookRepository->findForCandleChart($location, $type, $from, $to));
    }
}
