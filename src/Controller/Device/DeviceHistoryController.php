<?php

declare(strict_types=1);

namespace App\Controller\Device;

use App\Model\DeviceStatus;
use App\Model\Status;
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
                    $statuses = $helper->getHistory();
                } catch (\Exception) {}
            }
        }

        $history = [];

        /** @var array{DeviceStatus} $statuses */
        for ($i = 0; $i < count($statuses ?? []); $i++) {
            if ($i === 0 && $statuses[$i]->getStatus() == Status::INACTIVE) {
                $history[$i]['standby'] = $statuses[$i];

                continue;
            }

            $history[$i][$statuses[$i]->getStatus()->value] = $statuses[$i];
            $history[$i++][$statuses[$i]->getStatus()->value] = $statuses[$i];
        }

        return $this->json([
            'content' => $this->renderView('device/history.html.twig', [
                'history' => $history ? array_values($history) : null,
            ]),
        ]);
    }
}
