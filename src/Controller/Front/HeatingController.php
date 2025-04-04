<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Location\Location;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HeatingController extends AbstractController
{
    #[Route('/heating', name: 'app_front_heating')]
    public function index(): Response
    {
        return $this->render('front/heating/index.html.twig', [
            'locations' => Location::getHeatingLocations(),
        ]);
    }
}
