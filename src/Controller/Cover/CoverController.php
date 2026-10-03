<?php

declare(strict_types=1);

namespace App\Controller\Cover;

use App\Exception\ShellyDeviceUnavailableException;
use App\Service\Shelly\Cover\ShellyCoverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cover', name: 'app_cover_')]
final class CoverController extends AbstractController
{
    #[Route('/open-close', name: 'open_close', methods: ['PATCH'])]
    public function index(Request $request, ShellyCoverService $coverService): Response
    {
        $direction = $request->request->get('direction');

        if (!in_array($direction, ['open', 'close'], true)) {
            return $this->json(
                sprintf('%s is not a valid direction', (string) $direction),
                Response::HTTP_BAD_REQUEST,
            );
        }

        match ($direction) {
            'open'  => $coverService->open(),
            'close' => $coverService->close(),
        };

        return $this->json([]);
    }

    #[Route('/read', name: 'read', methods: ['GET'])]
    public function read(ShellyCoverService $coverService): Response
    {
        try {
            $status = $coverService->getStatus();
        } catch (ShellyDeviceUnavailableException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'last_direction' => $status['last_direction'],
            'state' => $status['state'],
        ]);
    }
}
