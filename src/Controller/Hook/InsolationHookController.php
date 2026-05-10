<?php

namespace App\Controller\Hook;

use App\Event\Hook\InsolationHookEvent;
use App\Service\AirQuality\InsolationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/hook/insolation', name: 'app_hook_insolation_')]
class InsolationHookController extends AbstractController
{
    #[Route('/garage/{value}', name: 'garage', priority: 5)]
    public function garageInsolation(
        string                   $value,
        InsolationService        $insolationService,
        EventDispatcherInterface $eventDispatcher,
    ): Response
    {
        $insolation = (float)$value;

        $insolationService->store($insolation);

        $eventDispatcher->dispatch(new InsolationHookEvent($insolation));

        return $this->json($value);
    }
}
