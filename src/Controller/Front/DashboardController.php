<?php

namespace App\Controller\Front;

use App\Entity\DeviceDailyStats;
use App\Repository\AirQualityRepository;
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
        AirQualityRepository                                            $airQualityRepository,
        WeatherForecastRepository                                       $weatherRepository,
    ): Response {
        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $i => $helper) {
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

            $devices[$i] = [
                'name'       => $helper->getDeviceName(),
                'deviceId'   => $helper->getDeviceId(),
                'history'    => $helper->getHistory(2),
                'dailyStats' => $dailyStats ?? null,
            ];

            if ($helper->getDeviceName() === 'kominek') {
                $devices[$i]['temperature'] = $hookRepository->findActualTempForLocation('kominek');
            }
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
            'heatingTemps' => [
                'supply' => $hookRepository->findActualTempForLocation('podl-zasilanie'),
                'recirculation' => $hookRepository->findActualTempForLocation('podl-powrot-recyrkulacja'),
                'return' => $hookRepository->findActualTempForLocation('podl-powrot-bufor'),
            ],
            'airQuality'  => $airQualityRepository->findLast(),
            'devices'     => $devices ?? [],
            'rooms'       => $rooms ?? [],
            'weather'     => $weatherRepository->findForecastForDate(),
        ]);
    }

    #[Route('/dashboard/heating-modal-form-render', name: 'app_front_heating_modal_form_render')]
    public function heatingFormRender(
        HeatingPumpService         $heatingPumpService,
        ScheduledProcessRepository $scheduledProcessRepository,
    ): Response {
        $heatingPumpSupply = $heatingPumpService->getActualState('pompa-zasilanie');
        $heatingPumpReturn = $heatingPumpService->getActualState('pompa-powrot');

        return $this->json([
            'form' => $this->renderView('front/_modals/_heating_controller_form.html.twig', [
                'isHeatingActive'    => $heatingPumpSupply['active'] && $heatingPumpReturn['active'],
                'heatingPump'        => [
                    'supply' => $heatingPumpSupply,
                    'return' => $heatingPumpReturn,
                ],
                'scheduledProcesses' => $scheduledProcessRepository->findHeatingProcessToExecute(),
            ]),
        ]);
    }
}
