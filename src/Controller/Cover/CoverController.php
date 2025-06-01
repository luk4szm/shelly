<?php

declare(strict_types=1);

namespace App\Controller\Cover;

use App\Service\Shelly\Cover\ShellyCoverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CoverController extends AbstractController
{
    #[Route('/cover/open-close', name: 'app_cover_index', methods: ['PATCH'])]
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
}
