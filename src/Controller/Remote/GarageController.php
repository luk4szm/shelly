<?php

declare(strict_types=1);

namespace App\Controller\Remote;

use App\Service\LogReader\GarageLogReader;
use App\Service\Shelly\Cover\ShellyGarageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/remote', name: 'app_remote_')]
class GarageController extends AbstractController
{
    #[Route('/garage', name: 'garage')]
    public function garage(ShellyGarageService $garageService, GarageLogReader $logReader): Response
    {
        try {
            $isOpen = $garageService->isOpen();
        } catch (\Exception $e) { }

        return $this->render('remote/garage.html.twig', [
            'is_open'    => $isOpen ?? null,
            'parsedLogs' => $logReader->getParsedLogs(),
        ]);
    }
}
