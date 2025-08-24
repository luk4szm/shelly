<?php

namespace App\Controller\Front;

use App\Entity\DeviceDailyStats;
use App\Repository\HookRepository;
use App\Service\DailyStats\DailyStatsCalculatorInterface;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use App\Service\Location\LocationFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_front_dashboard')]
    public function index(
        #[AutowireIterator('app.shelly.device_status_helper')] iterable $statusHelpers,
        #[AutowireIterator('app.shelly.daily_stats')] iterable          $dailyStatsCalculators,
        LocationFinder                                                  $locationFinder,
        HookRepository                                                  $hookRepository,
    ): Response {
        $rooms = array_merge($locationFinder->getLocations('rooms'));

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            /** @var DailyStatsCalculatorInterface $helper */
            foreach ($dailyStatsCalculators as $statsCalculator) {
                if ($statsCalculator->supports($helper->getDeviceName())) {
                    try {
                        $dailyStats = $statsCalculator->calculateDailyStats(new \DateTime());
                        continue;
                    } catch (\Exception) {
                    }

                    $dailyStats = new DeviceDailyStats($helper->getDeviceName(), new \DateTime());
                }
            }

            $devices[] = [
                'name'       => $helper->getDeviceName(),
                'deviceId'   => $helper->getDeviceId(),
                'history'    => $helper->getHistory(2),
                'dailyStats' => $dailyStats ?? null,
            ];
        }

        $locationCurrentState['bufor'] = [
            'temperature_15m' => $hookRepository->findActualTempForLocation('bufor'),
            'temperature_05m' => $hookRepository->findActualTempForLocation('bufor-solary'),
            'pressure'         => $hookRepository->findActualPressureForLocation('co'),
        ];

        foreach ($rooms as $location) {
            $locationCurrentState[$location] = [
                'temperature' => $hookRepository->findActualTempForLocation($location),
                'humidity' => $hookRepository->findActualHumidityForLocation($location),
            ];
        }

        return $this->render('front/dashboard/index.html.twig', [
            'devices'      => $devices ?? [],
            'locations'    => $locationCurrentState ?? [],
            'rooms'        => $rooms,
            'temperatures' => array_values($hookRepository->findActualTemps($rooms)),
            'humidity'     => array_values($hookRepository->findActualHumidity($rooms)),
        ]);
    }
}
