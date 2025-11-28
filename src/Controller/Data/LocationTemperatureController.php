<?php

declare(strict_types=1);

namespace App\Controller\Data;

use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Service\Location\LocationFinder;
use App\Utils\Hook\GraphHandler\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocationTemperatureController extends AbstractController
{
    #[Route('/data/temp', name: 'app_data_location_temperature_index', methods: ['GET'])]
    public function index(
        Request                 $request,
        HookRepository          $hookRepository,
        LocationFinder          $locationFinder,
        TemperatureGraphHandler $graphHandler,
    ): Response
    {
        $timeRange = $request->query->get('timeRange');
        $date      = $request->query->get('date');
        $location  = $request->query->get('location');
        $group     = $request->query->get('group');

        if ($timeRange !== null) {
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
                    $from = (new \DateTime($timeRange))->setTime(0, 0);
                    $to   = (new \DateTime($timeRange))->setTime(23, 59, 59);
            }
        } elseif ($date !== null) {
            $from = (new \DateTime($date))->setTime(0, 0);
            $to   = (new \DateTime($date))->setTime(23, 59, 59);
        } else {
            $from = new \DateTime("-8 hours");
            $to   = new \DateTime();
        }

        if ($location) {
            $hooks = $hookRepository->findLocationTemperatures($from, $to, $location === 'all' ? null : $location);
        } elseif($group) {
            $hooks = $hookRepository->findLocationTemperatures($from, $to, $locationFinder->getLocations($group));
        } else {
            $hooks = $hookRepository->findLocationTemperatures($from, $to);
        }

        if (empty($hooks)) {
            return $this->json([]);
        }

        if (empty($location) || $location === 'all') {
            return $this->json($graphHandler->prepareGroupedHooks($hooks));
        }

        return $this->json(
            array_map(function (Hook $hook) {
                return TemperatureGraphHandler::serialize($hook);
            }, $hooks)
        );
    }
}
