<?php

namespace App\Controller\Hook;

use App\Entity\Hook;
use App\Event\Hook\CoHookEvent;
use App\Event\Hook\TvHookEvent;
use App\Repository\HookRepository;
use App\Service\AirQuality\AirQualityService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HookController extends AbstractController
{
    #[Route('/hook/cover/{direction}', name: 'app_hoke_cover_save')]
    public function cover(string $direction, LoggerInterface $coverControllerLogger): Response
    {
        $message = match ($direction) {
            'open'  => 'Covers have been opened',
            'close' => 'Covers have been closed',
            default => throw new \InvalidArgumentException('Invalid cover direction'),
        };

        $coverControllerLogger->info($message, ['device' => 'switch']);

        return $this->json([]);
    }

    #[Route('/hook/air-quality', name: 'app_hoke_air_quality_save', methods: ['POST'])]
    public function airQuality(Request $request, AirQualityService $airQualityService): Response
    {
        if (!isset($request->getContent()['sensordatavalues'])) {
            return $this->json([], 400);
        }

        $sensorDataValues = $request->getContent()['sensordatavalues'];

        $airQualityService->saveData($sensorDataValues);

        return $this->json([]);
    }

    #[Route('/hook/garage/open-close', name: 'app_hoke_garage_save')]
    public function garage(LoggerInterface $garageControllerLogger): Response
    {
        $garageControllerLogger->info('The button was clicked', ['device' => 'switch']);

        return $this->json([]);
    }

    #[Route('/hook/{device}/{property}/{value}', name: 'app_hoke_save')]
    public function hook(
        string                   $device,
        string                   $property,
        string                   $value,
        HookRepository           $repository,
        EventDispatcherInterface $dispatcher
    ): Response
    {
        $hook = new Hook($device, $property, $value);

        $repository->save($hook);

        match ($device) {
            'tv'    => $dispatcher->dispatch(new TvHookEvent($hook)),
            'co'    => $dispatcher->dispatch(new CoHookEvent($hook)),
            default => null,
        };

        return $this->json($hook);
    }
}
