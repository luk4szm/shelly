<?php

declare(strict_types=1);

namespace App\Controller\Data;

use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Utils\Hook\Temperature\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocationTemperatureController extends AbstractController
{
    #[Route('/data/temp/{location}', name: 'app_data_location_temperature_index', methods: ['GET'])]
    public function index(
        HookRepository          $hookRepository,
        TemperatureGraphHandler $graphHandler,
        ?string                 $location = null,
    ): Response
    {
        $hooks = $hookRepository->findLocationTemperatures($location);

        if ($location === null) {
            return $this->json($graphHandler->prepareGroupedHooks($hooks));
        }

        return $this->json(
            array_map(function (Hook $hook) {
                return TemperatureGraphHandler::serialize($hook);
            }, $hooks)
        );
    }
}
