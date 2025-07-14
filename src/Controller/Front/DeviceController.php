<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\DeviceDailyStats;
use App\Entity\Hook;
use App\Model\DateRange;
use App\Repository\HookRepository;
use App\Service\DailyStats\DailyStatsCalculatorInterface;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use App\Utils\Hook\GraphHandler\PowerGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeviceController extends AbstractController
{
    #[Route('/device/{device}', name:'app_front_device')]
    public function index(
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable $statusHelpers,
        #[AutowireIterator('app.shelly.daily_stats')]
        iterable $dailyStatsCalculators,
        Request  $request,
        string   $device,
    ): Response
    {
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

            $device = [
                'name'       => $helper->getDeviceName(),
                'deviceId'   => $helper->getDeviceId(),
                'history'    => $helper->getHistory(2),
                'dailyStats' => $dailyStats ?? null,
            ];

            break;
        }

        return $this->render('front/device/index.html.twig', [
            'device' => $device,
        ]);
    }

    #[Route('/device/{device}/power-data', name:'app_front_device_power_data')]
    public function getPowerData(
        string            $device,
        Request           $request,
        HookRepository    $hookRepository,
    ): Response
    {
        $date  = new \DateTime($request->get('date'));
        $hooks = $hookRepository->findHooksByDeviceAndDate($device, $date);

        return $this->json(
            array_map(function (Hook $hook) {
                return PowerGraphHandler::serialize($hook);
            }, $hooks)
        );
    }
}
