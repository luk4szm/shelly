<?php

declare(strict_types=1);

namespace App\Controller\Data;

use App\Service\Shelly\Switch\ShellySwitchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeviceStatusController extends AbstractController
{
    #[Route('/data/status', name: 'app_data_device_status_index', methods: ['GET'])]
    public function index(Request $request, ShellySwitchService $switchService): Response
    {
        $status = $switchService->getStatus($request->get('deviceId'));

        return $this->json($status);
    }
}
