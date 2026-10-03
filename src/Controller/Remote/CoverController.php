<?php

declare(strict_types=1);

namespace App\Controller\Remote;

use App\Service\LogReader\CoverLogReader;
use App\Service\Shelly\Cover\ShellyCoverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/remote', name: 'app_remote_')]
class CoverController extends AbstractController
{
    #[Route('/cover', name: 'cover')]
    public function cover(ShellyCoverService $coverService, CoverLogReader $logReader): Response
    {
        try {
            $status = $coverService->getStatus();
        } catch (\Exception $e) { }

        return $this->render('remote/cover.html.twig', [
            'last_direction' => $status['last_direction'] ?? null,
            'parsedLogs'     => $logReader->getParsedLogs(),
        ]);
    }
}
