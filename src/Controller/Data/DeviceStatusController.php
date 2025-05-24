<?php

declare(strict_types=1);

namespace App\Controller\Data;

use App\Service\Shelly\Relay\RelayService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeviceStatusController extends AbstractController
{
    #[Route('/data/status', name: 'app_data_device_status_index', methods: ['GET'])]
    public function index(RelayService $powerMonitorService): Response
    {
        // TODO: retrieve device id
        $status = $powerMonitorService->getStatus('543204705c18');

        return $this->json($status);
    }
}
