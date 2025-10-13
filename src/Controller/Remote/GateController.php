<?php

declare(strict_types=1);

namespace App\Controller\Remote;

use App\Service\Gate\SuplaGateOpener;
use App\Service\LogReader\GateLogReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/remote', name: 'app_remote_')]
class GateController extends AbstractController
{
    #[Route('/gate', name: 'gate')]
    public function gate(SuplaGateOpener $gateOpener, GateLogReader $logReader): Response
    {
        $gateResponse = $gateOpener->read();

        if (!isset($gateResponse['connected']) || $gateResponse['connected'] !== true) {
            throw new \RuntimeException('Gate is disconnected.');
        }

        return $this->render('remote/gate.html.twig', [
            'is_open' => !($gateResponse['hi'] === true),
            'logs'    => $logReader->getLastLogLines(),
        ]);
    }
}
