<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\DeviceDailyStats;
use App\Entity\Hook;
use App\Model\DateRange;
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

            $device = [
                'name'       => $helper->getDeviceName(),
                'deviceId'   => $helper->getDeviceId(),
                'history'    => $helper->getHistory(dateRange: $dateRange, grouped: true),
                'dailyStats' => $dailyStats ?? null,
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

                    $dailyStats = new DeviceDailyStats($helper->getDeviceName(), $date);
                }
            }

            $dateRange = new DateRange(
                (clone $date)->setTime(0, 0),
                (clone $date)->setTime(23, 59, 59),
            );

            $monthlyData = array_reverse($statsRepository->findForDeviceAndMonth($device, $date));
            array_unshift($monthlyData, $dailyStats ?? null);

            $monthlyData   = array_filter($monthlyData);
            $initialValues = ['inclusions' => 0, 'energy' => 0, 'time' => 0,];

            $monthlyStats = array_reduce($monthlyData, static function ($carry, DeviceDailyStats $dailyStats) {
                $carry['inclusions'] += $dailyStats->getInclusions();
                $carry['energy']     += $dailyStats->getEnergy();
                $carry['time']       += $dailyStats->getTotalActiveTime();

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

    #[Route('/power-data', name: 'power_data')]
    public function getPowerData(
        string         $device,
        Request        $request,
        HookRepository $hookRepository,
    ): Response {
        $date  = new \DateTime($request->get('date'));
        $hooks = $hookRepository->findHooksByDeviceAndDate($device, $date);

        return $this->json(
            array_map(function (Hook $hook) {
                return PowerGraphHandler::serialize($hook);
            }, $hooks)
        );
    }
}
