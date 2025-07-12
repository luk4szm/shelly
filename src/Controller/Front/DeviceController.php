<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeviceController extends AbstractController
{
    #[Route('/device/{device}', name:'app_front_device')]
    public function index(string $device): Response
    {
        return $this->json($device);
    }
}
