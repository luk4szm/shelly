<?php

declare(strict_types=1);

namespace App\Controller\GasMeter;

use App\Model\DeviceStatus;
use App\Repository\GasMeterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GasMeterHistoryController extends AbstractController
{
    #[Route('/gas/meter/history', name: 'app_gas_meter_history_index', methods: ['GET'])]
    public function index(GasMeterRepository $repository): Response
    {
        /** @var array{DeviceStatus} $statuses */
        return $this->json([
            'content' => $this->renderView('gas_meter/history.html.twig', [
                'history' => $repository->findForLastMonth(),
            ]),
        ]);
    }
}
