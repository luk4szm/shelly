<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Repository\HookRepository;
use App\Service\Location\LocationFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocationController extends AbstractController
{
    #[Route('/location/{location}', name: 'app_front_location')]
    public function index(
        HookRepository $hookRepository,
        LocationFinder $locationFinder,
        string         $location
    ): Response {
        return $this->render('front/location/index.html.twig', [
            'salonTemp' => $hookRepository->findGroupedHooks('salon', 'temp'),
            'salonHum'  => $hookRepository->findGroupedHooks('salon', 'humidity'),
        ]);
    }
}
