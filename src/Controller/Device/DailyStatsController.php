<?php

declare(strict_types=1);

namespace App\Controller\Device;

use App\Repository\DeviceDailyStatsRepository;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DailyStatsController extends AbstractController
{
    #[Route('/device/daily-stats', name: 'app_device_daily_stats_index', methods: ['GET'])]
    public function index(
        Request                    $request,
        DeviceDailyStatsRepository $statsRepository,
        #[AutowireIterator('app.shelly.daily_stats')]
        iterable                   $dailyStatsCalculators,
    ): Response {
        $deviceName = $request->query->get('device');

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($dailyStatsCalculators as $statsCalculator) {
            if ($statsCalculator->supports($deviceName)) {
                try {
                    $dailyStats = $statsCalculator->calculateDailyStats(new \DateTime());
                } catch (\Exception) {
                    $dailyStats = null;
                }
            }
        }

        return $this->json([
            'content' => $this->renderView('device/daily_stats.html.twig', [
                'todayStats'     => $dailyStats ?? null,
                'historicalData' => array_reverse($statsRepository->findForDeviceFromLastDays($deviceName)),
            ]),
        ]);
    }
}
