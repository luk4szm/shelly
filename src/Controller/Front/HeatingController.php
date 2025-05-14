<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Location\LocationFinder;
use App\Repository\HookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HeatingController extends AbstractController
{
    #[Route('/heating', name: 'app_front_heating')]
    public function index(
        Request        $request,
        HookRepository $hookRepository,
        LocationFinder $locationFinder,
    ): Response
    {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'salonTemp' => $hookRepository->findGroupedHooks('salon', 'temp'),
                'salonHum'  => $hookRepository->findGroupedHooks('salon', 'humidity'),
            ]);
        }

        return $this->render('front/heating/index.html.twig', [
            'locations' => array_merge($locationFinder->getLocations('buffer'), $locationFinder->getLocations('rooms')),
        ]);
    }
}
