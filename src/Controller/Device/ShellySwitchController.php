<?php

declare(strict_types=1);

namespace App\Controller\Device;

use App\Model\Request\DeviceSwitchRequestPayload;
use App\Service\Shelly\Switch\ShellySwitchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/device/switch', name: 'app_device_switch_')]
class ShellySwitchController extends AbstractController
{
    #[Route('/turn', name: 'turn_on')]
    public function turn(
        #[MapRequestPayload] DeviceSwitchRequestPayload $payload,
        ShellySwitchService                             $shellySwitchService,
    ): Response
    {
        $shellySwitchService->switch(
            $payload->deviceId,
            $payload->channel,
            $payload->action,
        );

        return $this->json([]);
    }
}
