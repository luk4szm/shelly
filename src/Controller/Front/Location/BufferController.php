<?php

declare(strict_types=1);

namespace App\Controller\Front\Location;

use App\Repository\HookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location', name: 'app_front_location_')]
class BufferController extends AbstractController
{
    #[Route('/buffer', name: 'buffer')]
    public function index(HookRepository $hookRepository): Response
    {
        return $this->render('front/location/buffer/index.html.twig', [
            'temperature_15m' => $hookRepository->findGroupedHooks('salon', 'temp'),
            'temperature_05m'  => $hookRepository->findGroupedHooks('salon', 'humidity'),
        ]);
    }
}
