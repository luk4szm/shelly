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
        return $this->json(['last_direction' => $coverService->getLastDirection()]);
    }
}
