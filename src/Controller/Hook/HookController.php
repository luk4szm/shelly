<?php

namespace App\Controller\Hook;

use App\Entity\Hook;
use App\Event\Hook\BoilerHookEvent;
use App\Event\Hook\CoHookEvent;
use App\Event\Hook\FireplaceHookEvent;
use App\Event\Hook\TvHookEvent;
use App\Event\SuplaGateOpenEvent;
use App\Repository\HookRepository;
use App\Service\AirQuality\AirQualityService;
use App\Service\Gate\SuplaGateOpener;
use App\Service\Hydration\HydrationLogger;
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
        $data = json_decode($request->getContent());

        if (!property_exists($data, 'sensordatavalues')) {
            return $this->json([], 400);
        }

        $airQualityService->saveData($data->sensordatavalues);

        return $this->json([]);
    }

    #[Route('/hook/hydration/{valve}/{action}', name: 'app_hoke_hydration_save')]
    public function hydration(string $valve, string $action, HydrationLogger $hydrationLogger): Response
    {
        match ($action) {
            'start' => $hydrationLogger->start($valve),
            'stop'  => $hydrationLogger->stop($valve),
        };

        return $this->json([]);
    }

    #[Route('/hook/garage/open-close', name: 'app_hoke_garage_save')]
    public function garage(LoggerInterface $garageControllerLogger): Response
    {
        $garageControllerLogger->info('The button was clicked', ['device' => 'switch']);

        return $this->json([]);
    }

    #[Route('/hook/gate/open/remote/{key}', name: 'app_hook_gate_open_from_garmin_watch')]
    public function gateOpenFromGarminWatch(
        Request                  $request,
        SuplaGateOpener          $gateOpener,
        EventDispatcherInterface $dispatcher,
        string                   $key
    ): Response
    {
        if ($key !== $_ENV['GATE_REMOTE_CONTROLLER_KEY']) {
            return $this->json([]);
        }

        $gateOpener->sendOpenRequest('not logged in', $request->query->get('deviceName'));
        $dispatcher->dispatch(new SuplaGateOpenEvent('open', 'not logged in', $request->query->get('deviceName')));

        return new Response('OK', Response::HTTP_OK, ['Content-Type' => 'text/plain']);
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
            'tv'      => $dispatcher->dispatch(new TvHookEvent($hook)),
            'co'      => $dispatcher->dispatch(new CoHookEvent($hook)),
            'kominek' => $dispatcher->dispatch(new FireplaceHookEvent($hook)),
            'piec'    => $dispatcher->dispatch(new BoilerHookEvent($hook)),
            default => null,
        };

        return $this->json($hook);
    }
}
