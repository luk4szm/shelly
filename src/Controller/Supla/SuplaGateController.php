<?php

declare(strict_types=1);

namespace App\Controller\Supla;

use App\Service\Gate\SuplaGateOpener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/supla/gate', name: 'supla_gate')]
class SuplaGateController extends AbstractController
{
    #[Route('/open', name: 'open', methods: ['PATCH'])]
    public function open(SuplaGateOpener $gateOpener): Response
    {
        return $this->json($gateOpener->open());
    }

    #[Route('/open-close', name: 'open_close', methods: ['PATCH'])]
    public function openClose(SuplaGateOpener $gateOpener): Response
    {
        return $this->json($gateOpener->sendOpenCloseSimpleRequest());
    }

}
