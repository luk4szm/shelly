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
    #[Route('/data/temp/{location}/{from}/{to}', name: 'app_data_location_temperature_index', methods: ['GET'])]
    public function index(
        HookRepository          $hookRepository,
        TemperatureGraphHandler $graphHandler,
        ?string                 $location = null,
        ?string                 $from = null,
        ?string                 $to = null,
    ): Response
    {
        $location = $location === null ? 'all' : $location;
        $from     = $from === null ? new \DateTime("-8 hours") : new \DateTime($from);
        $to       = $to === null ? new \DateTime() : new \DateTime($to);

        $hooks = $hookRepository->findLocationTemperatures($from, $to, $location);

        if (empty($hooks)) {
            return $this->json([]);
        }

        if ($location === 'all') {
            return $this->json($graphHandler->prepareGroupedHooks($hooks));
        }

        return $this->json(
            array_map(function (Hook $hook) {
                return TemperatureGraphHandler::serialize($hook);
            }, $hooks)
        );
    }
}
