<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Location\Location;
use App\Repository\HookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HeatingController extends AbstractController
{
    #[Route('/heating', name: 'app_front_heating')]
    public function index(Request $request, HookRepository $hookRepository): Response
    {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'salonTemp' => $hookRepository->findGroupedHooks('salon', 'temp'),
                'salonHum'  => $hookRepository->findGroupedHooks('salon', 'humidity'),
            ]);
        }

        return $this->render('front/heating/index.html.twig', [
            'locations' => Location::getHeatingLocations(),
        ]);
    }
}
