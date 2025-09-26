<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\DeviceDailyStats;
use App\Entity\Hook;
use App\Model\DateRange;
use App\Model\Device\Boiler;
use App\Repository\DeviceDailyStatsRepository;
use App\Repository\HookRepository;
use App\Service\DailyStats\DailyStatsCalculatorInterface;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use App\Utils\Hook\GraphHandler\PowerGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/device/{device}', name: 'app_front_device_')]
class DeviceController extends AbstractController
{
    #[Route('/daily', name: 'daily')]
    public function daily(
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable $statusHelpers,
        #[AutowireIterator('app.shelly.daily_stats')]
        iterable $dailyStatsCalculators,
        Request  $request,
        string   $device,
    ): Response {
        $date = new \DateTime($request->get('date', ''));

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            if (!$helper->supports($device)) {
                continue;
            }

            /** @var DailyStatsCalculatorInterface $helper */
            foreach ($dailyStatsCalculators as $statsCalculator) {
                if ($statsCalculator->supports($helper->getDeviceName())) {
                    try {
                        $dailyStats = $statsCalculator->calculateDailyStats($date);
                        break;
                    } catch (\Exception) {
                    }

                    $dailyStats = new DeviceDailyStats($helper->getDeviceName(), $date);
                }
            }

            $dateRange = new DateRange(
                (clone $date)->setTime(0, 0),
                (clone $date)->setTime(23, 59, 59),
            );

            if ($device === 'piec' && isset($dailyStats)) {
                $gas = $dailyStats->getEnergy() * Boiler::EST_FUEL_CONSUME;
            }

            $device = [
                'name'       => $helper->getDeviceName(),
                'deviceId'   => $helper->getDeviceId(),
                'history'    => $helper->getHistory(dateRange: $dateRange, grouped: true),
                'dailyStats' => $dailyStats ?? null,
                'gas'        => $gas ?? 0,
            ];

            break;
        }

        return $this->render('front/device/daily.html.twig', [
            'device' => $device,
        ]);
    }

    #[Route('/monthly', name: 'monthly')]
    public function monthly(
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable                   $statusHelpers,
        #[AutowireIterator('app.shelly.daily_stats')]
        iterable                   $dailyStatsCalculators,
        Request                    $request,
        DeviceDailyStatsRepository $statsRepository,
        string                     $device,
    ): Response {
        $date = new \DateTime($request->get('date', ''));

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            if (!$helper->supports($device)) {
                continue;
            }

            /** @var DailyStatsCalculatorInterface $helper */
            foreach ($dailyStatsCalculators as $statsCalculator) {
                if ($statsCalculator->supports($helper->getDeviceName())) {
                    try {
                        $dailyStats = $statsCalculator->calculateDailyStats($date);
                        break;
                    } catch (\Exception) {
                    }

//                    $dailyStats = new DeviceDailyStats($helper->getDeviceName(), $date);
                }
            }

            $dateRange = new DateRange(
                (clone $date)->setTime(0, 0),
                (clone $date)->setTime(23, 59, 59),
            );

            $monthlyData = array_reverse($statsRepository->findForDeviceAndMonth($device, $date));

            if ($date->format('Y-m') === (new \DateTime())->format('Y-m')) {
                array_unshift($monthlyData, $dailyStats ?? null);
            }

            $monthlyData   = array_filter($monthlyData);
            $initialValues = ['inclusions' => 0, 'energy' => 0, 'time' => 0, 'gas' => 0];

            $monthlyStats = array_reduce($monthlyData, static function ($carry, DeviceDailyStats $dailyStats) use ($device) {
                $carry['inclusions'] += $dailyStats->getInclusions();
                $carry['energy']     += $dailyStats->getEnergy();
                $carry['time']       += $dailyStats->getTotalActiveTime();

                if ($device === 'piec') {
                    $carry['gas'] += $dailyStats->getEnergy() * Boiler::EST_FUEL_CONSUME;
                }

                return $carry;
            }, $initialValues);

            $device = [
                'name'         => $helper->getDeviceName(),
                'deviceId'     => $helper->getDeviceId(),
                'history'      => $helper->getHistory(dateRange: $dateRange, grouped: true),
                'dailyStats'   => $dailyStats ?? null,
                'monthlyData'  => $monthlyData,
                'monthlyStats' => $monthlyStats ?? [],
            ];

            break;
        }

        return $this->render('front/device/monthly.html.twig', [
            'device' => $device,
        ]);
    }

    #[Route('/yearly', name: 'yearly')]
    public function yearly(
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable                   $statusHelpers,
        Request                    $request,
        DeviceDailyStatsRepository $statsRepository,
        string                     $device,
    ): Response {
        $date = new \DateTime($request->get('date', ''));
        $year = (int)$date->format('Y');

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            if (!$helper->supports($device)) {
                continue;
            }

            $monthlyStats = [];

            for ($month = 1; $month <= 12; $month++) {
                $monthDate = (new \DateTime())->setDate($year, $month, 1);
                $monthlyData = $statsRepository->findForDeviceAndMonth($device, $monthDate);

                if (empty($monthlyData)) {
                    $monthlyStats[$month] = [
                        'inclusions' => 0,
                        'energy' => 0,
                        'time' => 0,
                        'gas' => 0,
                        'month' => $monthDate->format('F'),
                    ];
                    continue;
                }

                $initialValues = ['inclusions' => 0, 'energy' => 0, 'time' => 0, 'gas' => 0];
                $monthSummary = array_reduce($monthlyData, function ($carry, DeviceDailyStats $dailyStats) use ($device) {
                    $carry['inclusions'] += $dailyStats->getInclusions();
                    $carry['energy']     += $dailyStats->getEnergy();
                    $carry['time']       += $dailyStats->getTotalActiveTime();

                    if ($device === 'piec') {
                        $carry['gas'] += $dailyStats->getEnergy() * Boiler::EST_FUEL_CONSUME;
                    }

                    return $carry;
                }, $initialValues);
                $monthSummary['month'] = $monthDate->format('F');

                $monthlyStats[$month] = $monthSummary;
            }

            $yearlyStats = [
                'inclusions' => array_sum(array_column($monthlyStats, 'inclusions')),
                'energy' => array_sum(array_column($monthlyStats, 'energy')),
                'time' => array_sum(array_column($monthlyStats, 'time')),
                'gas' => array_sum(array_column($monthlyStats, 'gas')),
            ];


            $device = [
                'name'         => $helper->getDeviceName(),
                'deviceId'     => $helper->getDeviceId(),
                'monthlyStats' => $monthlyStats,
                'yearlyStats'  => $yearlyStats,
                'year'         => $year,
            ];

            break;
        }

        return $this->render('front/device/yearly.html.twig', [
            'device' => $device,
        ]);
    }

    #[Route('/power-data', name: 'power_data')]
    public function getPowerData(
        string         $device,
        Request        $request,
        HookRepository $hookRepository,
    ): Response {
        $date  = new \DateTime($request->get('date'));
        $hooks = $hookRepository->findHooksByDeviceAndDate($device, $date);

        // clone last hook of previous day to chart start
        $dateImmutable          = new \DateTimeImmutable($date->format('Y-m-dT00:00:00'));
        $lastHookOfPreviousDate = $hookRepository->findLastHookOfDay($device, (clone $date)->modify('-1 day'));

        if ($lastHookOfPreviousDate !== null) {
             array_unshift($hooks, $lastHookOfPreviousDate->setCreatedAt($dateImmutable));
        }

        // clone last hook of device to end of chart
        $hooks[] = $request->get('date') === (new \DateTime())->format('Y-m-d')
            ? (clone end($hooks))->setCreatedAt(new \DateTimeImmutable())
            : (clone end($hooks))->setCreatedAt(new \DateTimeImmutable($date->format('Y-m-dT23:59:59')));

        return $this->json(
            array_map(function (Hook $hook) {
                return PowerGraphHandler::serialize($hook);
            }, $hooks)
        );
    }
}
