<?php

declare(strict_types=1);

namespace App\Controller\Front\Location;

use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Utils\Hook\GraphHandler\HumidityGraphHandler;
use App\Utils\Hook\GraphHandler\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location/{location}', name: 'app_front_default_location_')]
class LocationController extends AbstractController
{
    #[Route('/{type}', name: 'index', requirements: ['type' => '(?!get-data).+'])]
    public function daily(string $location, string $type): Response
    {
        return $this->render('front/location/default/index.html.twig', [
            'type'     => $type,
            'location' => $location,
        ]);
    }

    #[Route('/get-data', name: 'get_data')]
    public function getData(
        Request        $request,
        HookRepository $hookRepository,
        string         $location,
    ): Response {
        $date = $request->get('date', '');
        $type = $request->get('type');

        switch ($type) {
            case 'daily':
                $from = (new \DateTime($date))->setTime(0, 0);
                $to   = (new \DateTime($date))->setTime(23, 59, 59);

                return $this->json([
                    'temperature' => array_map(function (Hook $hook) {
                        return TemperatureGraphHandler::serialize($hook);
                    }, $hookRepository->findLocationTemperatures($from, $to, $location)),
                    'humidity'    => array_map(function (Hook $hook) {
                        return HumidityGraphHandler::serialize($hook);
                    }, $hookRepository->findLocationHumidity($from, $to, $location)),
                ]);
            case 'monthly':
                $from = (new \DateTime($date))->modify('first day of this month')->setTime(0, 0);
                $to   = (new \DateTime($date))->modify('last day of this month')->setTime(23, 59, 59);

                return $this->json([
                    'temperature' => $hookRepository->findMinMaxForDeviceAndProperty($location, 'temp', $from, $to),
                    'humidity'    => $hookRepository->findMinMaxForDeviceAndProperty($location, 'humidity', $from, $to),
                ]);
        }

        throw new BadRequestException('Unrecognized type ' . $type);
    }
}
