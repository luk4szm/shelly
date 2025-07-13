<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GasMeterController extends AbstractController
{
    #[Route('/gas-meter', name: 'app_front_gas_meter')]
    public function index(): Response
    {
        return $this->json('gas meter');
    }
}
