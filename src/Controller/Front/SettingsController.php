<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Enum\DaylightMode;
use App\Enum\OccupancyMode;
use App\Model\Request\SettingsRequestPayload;
use App\Repository\ConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/settings', name: 'app_front_settings_')]
final class SettingsController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(ConfigRepository $repository): Response
    {
        return $this->render('front/settings/index.html.twig', [
            'configs'        => $repository->findAll(),
            'occupancyModes' => OccupancyMode::cases(),
            'daylightModes'  => DaylightMode::cases(),
        ]);
    }

    #[Route('/set', name: 'set', methods: ['POST'])]
    public function set(
        #[MapRequestPayload] SettingsRequestPayload $payload,
        ConfigRepository                            $repository,
    ): Response
    {
        $repository->updateValueByName($payload->name, $payload->value);

        return $this->json([]);
    }
}
