<?php

declare(strict_types=1);

namespace App\Controller\Data;

use App\Entity\Hook;
use App\Model\Location\Location;
use App\Repository\HookRepository;
use App\Utils\Hook\Temperature\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocationTemperatureController extends AbstractController
{
    #[Route('/data/temp/{location}', name: 'app_data_location_temperature_index', methods: ['GET'])]
    public function index(
        Request                 $request,
        HookRepository          $hookRepository,
        TemperatureGraphHandler $graphHandler,
        ?string                 $location = null,
    ): Response
    {
        $timeRange = $request->get('timeRange');
        $location  = $location === null ? 'all' : $location;

        switch ($timeRange) {
            case 'today':
                $from = (new \DateTime())->setTime(0, 0);
                $to   = new \DateTime();
                break;
            case 'last_day':
                $from = new \DateTime("-1 day");
                $to   = new \DateTime();
                break;
            case 'last_week':
                $from = new \DateTime("-7 days");
                $to   = new \DateTime();
                break;
            case 'last_8h':
                $from = new \DateTime("-8 hours");
                $to   = new \DateTime();
                break;
            default:
                if (is_string($timeRange)) {
                    $from = (new \DateTime($timeRange))->setTime(0, 0);
                    $to   = (new \DateTime($timeRange))->setTime(23, 59, 59);
                } else {
                    $from = (new \DateTime())->setTime(0, 0);
                    $to   = new \DateTime();
                }

                break;
        }

        $hooks = $location === 'heating'
            ? $hookRepository->findLocationTemperatures($from, $to, Location::getHeatingLocations())
            : $hookRepository->findLocationTemperatures($from, $to, $location);

        if (empty($hooks)) {
            return $this->json([]);
        }

        if (in_array($location, ['all', 'heating'])) {
            return $this->json($graphHandler->prepareGroupedHooks($hooks));
        }

        return $this->json(
            array_map(function (Hook $hook) {
                return TemperatureGraphHandler::serialize($hook);
            }, $hooks)
        );
    }
}
