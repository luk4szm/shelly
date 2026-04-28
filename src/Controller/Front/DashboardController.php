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
        $devices = [];
        $helpers = iterator_to_array($statusHelpers);

        usort($helpers, fn(DeviceStatusHelperInterface $a, DeviceStatusHelperInterface $b) => $b->getPriority() <=> $a->getPriority());

        foreach ($helpers as $i => $helper) {
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

        $airQuality = $airQualityRepository->findLast();

        $temperature15m    = $hookRepository->findActualTempForLocation('bufor');
        $temperature05m    = $hookRepository->findActualTempForLocation('bufor-solary');
        $tempRecirculation = $hookRepository->findActualTempForLocation('podl-powrot-recyrkulacja');
        $bufferEnergy      = 1.163 * (($temperature15m->getValue() + $temperature05m->getValue()) / 2 - $tempRecirculation->getValue());

        return $this->render('front/dashboard/index.html.twig', [
            'buffer'      => [
                'energy'          => $bufferEnergy,
                'temperature_15m' => $temperature15m,
                'temperature_05m' => $temperature05m,
                'pressure'        => $hookRepository->findActualPressureForLocation('co'),
            ],
            'heatingPump' => [
                'supply' => $heatingPumpService->getActualState('pompa-zasilanie'),
                'return' => $heatingPumpService->getActualState('pompa-powrot'),
            ],
            'heatingTemps' => [
                'supplyTop'     => $hookRepository->findActualTempForLocation('rozdzielnica-gora-zasilanie'),
                'supplyBottom'  => $hookRepository->findActualTempForLocation('rozdzielnica-dol-zasilanie'),
                'supply'        => $hookRepository->findActualTempForLocation('podl-zasilanie'),
                'recirculation' => $tempRecirculation,
                'return'        => $hookRepository->findActualTempForLocation('podl-powrot-bufor'),
                'returnTop'     => $hookRepository->findActualTempForLocation('rozdzielnica-gora-powrot'),
                'returnBottom'  => $hookRepository->findActualTempForLocation('rozdzielnica-dol-powrot'),
            ],
            'airQuality'  => $airQuality,
            'insolation'  => $airQuality->getInsolation() === null
                ? $airQualityRepository->findLastInsolationReading()
                : $airQuality->getInsolation(),
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
