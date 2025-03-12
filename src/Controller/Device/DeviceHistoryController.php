<?php

declare(strict_types=1);

namespace App\Controller\Device;

use App\Model\DeviceStatus;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeviceHistoryController extends AbstractController
{
    #[Route('/device/history', name: 'app_device_history_index', methods: ['GET'])]
    public function index(
        Request  $request,
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable $statusHelpers,
    ): Response
    {
        $deviceName = $request->query->get('device');

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            if ($helper->supports($deviceName)) {
                try {
                    $history = $helper->getHistory(grouped: true);
                } catch (\Exception) {}
            }
        }

        /** @var array{DeviceStatus} $statuses */
        return $this->json([
            'content' => $this->renderView('device/history.html.twig', [
                'history' => $history ?? null,
            ]),
        ]);
    }
}
