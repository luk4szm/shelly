<?php

namespace App\Controller\Front;

use App\Entity\DeviceDailyStats;
use App\Repository\HookRepository;
use App\Repository\Process\ScheduledProcessRepository;
use App\Repository\WeatherForecastRepository;
use App\Service\DailyStats\DailyStatsCalculatorInterface;
use App\Service\Device\HeatingPumpService;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use App\Service\Location\LocationFinder;
use App\Service\Processable\TurnOffHeatingProcess;
use App\Service\Processable\TurnOnHeatingProcess;
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
        HeatingPumpService                                              $heatingPumpService,
        ScheduledProcessRepository                                      $scheduledProcessRepository,
        WeatherForecastRepository                                       $weatherRepository,
    ): Response {
        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            if (!$helper->showOnDashboard()) {
                continue;
            }

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

        foreach ($locationFinder->getLocations('rooms') as $roomName) {
            $rooms[$roomName] = [
                'temperature' => $hookRepository->findActualTempForLocation($roomName),
                'humidity'    => $hookRepository->findActualHumidityForLocation($roomName),
            ];
        }

        return $this->render('front/dashboard/index.html.twig', [
            'buffer'      => [
                'temperature_15m' => $hookRepository->findActualTempForLocation('bufor'),
                'temperature_05m' => $hookRepository->findActualTempForLocation('bufor-solary'),
                'pressure'        => $hookRepository->findActualPressureForLocation('co'),
            ],
            'heatingPump' => [
                'supply' => $heatingPumpService->getActualState('pompa-zasilanie'),
                'return' => $heatingPumpService->getActualState('pompa-powrot'),
            ],
            'scheduledProcesses' => [
                'heatingTurnOn'  => $scheduledProcessRepository->findNextProcessToExecute(TurnOnHeatingProcess::NAME),
                'heatingTurnOff' => $scheduledProcessRepository->findNextProcessToExecute(TurnOffHeatingProcess::NAME),
            ],
            'devices'     => $devices ?? [],
            'rooms'       => $rooms ?? [],
            'weather'     => $weatherRepository->findActualForecast(),
        ]);
    }
}
