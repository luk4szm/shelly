<?php

namespace App\Controller\Hook;

use App\Service\AirQuality\InsolationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/hook/insolation', name: 'app_hook_insolation_')]
class InsolationHookController extends AbstractController
{
    #[Route('/garage/{value}', name: 'garage', priority: 5)]
    public function garageInsolation(InsolationService $insolationService, string $value): Response
    {
        $insolationService->store((float)$value);

        return $this->json($value);
    }
}
