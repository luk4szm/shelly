<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Location\LocationFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HeatingController extends AbstractController
{
    #[Route('/heating', name: 'app_front_heating')]
    public function index(LocationFinder $locationFinder): Response
    {
        return $this->render('front/heating/index.html.twig', [
            'locations' => array_merge($locationFinder->getLocations('buffer'), $locationFinder->getLocations('rooms')),
        ]);
    }
}
