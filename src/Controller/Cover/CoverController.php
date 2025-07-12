<?php

declare(strict_types=1);

namespace App\Controller\Cover;

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
        match ($request->get('direction')) {
            'open'  => $coverService->open(),
            'close' => $coverService->close(),
            default => $this->json(
                sprintf("%s is not a valid direction", $request->get('direction')),
                Response::HTTP_BAD_REQUEST
            ),
        };

        return $this->json([]);
    }

    #[Route('/read', name: 'read', methods: ['GET'])]
    public function read(ShellyCoverService $coverService): Response
    {
        try {
            $lastDirection = $coverService->getLastDirection();
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'HTTP/1.1 429 Too Many Requests')) {
                return $this->json(['error' => 'Too many requests. Wait 1 second and try again.'], Response::HTTP_TOO_MANY_REQUESTS);
            }

            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['last_direction' => $lastDirection]);
    }
}
