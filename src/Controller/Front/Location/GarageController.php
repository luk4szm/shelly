<?php

declare(strict_types=1);

namespace App\Controller\Front\Location;

use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Utils\Hook\GraphHandler\HumidityGraphHandler;
use App\Utils\Hook\GraphHandler\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location/garage', name: 'app_front_location_garage_')]
class GarageController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response {
        return $this->render('front/location/garage/index.html.twig');
    }

    #[Route('/get-data', name: 'get_data')]
    public function getData(
        Request        $request,
        HookRepository $hookRepository,
    ): Response {
        $date = $request->get('date', '');
        $from = (new \DateTime($date))->setTime(0, 0);
        $to   = (new \DateTime($date))->setTime(23, 59, 59);

        return $this->json([
            'temperature' => array_map(function (Hook $hook) {
                return TemperatureGraphHandler::serialize($hook);
            }, $hookRepository->findLocationTemperatures($from, $to, 'garage')),
            'humidity'    => array_map(function (Hook $hook) {
                return HumidityGraphHandler::serialize($hook);
            }, $hookRepository->findLocationHumidity($from, $to, 'garage')),
        ]);
    }
}
