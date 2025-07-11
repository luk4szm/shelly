<?php

declare(strict_types=1);

namespace App\Controller\Data;

use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Service\Location\LocationFinder;
use App\Utils\Hook\Temperature\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HeatingSourcesController extends AbstractController
{
    #[Route('/data/heating-sources', name: 'app_data_heating_sources_index', methods: ['GET'])]
    public function index(
        Request                 $request,
        HookRepository          $hookRepository,
        LocationFinder          $locationFinder,
        TemperatureGraphHandler $graphHandler,
    ): Response
    {
        return $this->json([
            [
                "source" => "Pompa Ciepła",
                "start"  => "2023-11-21T02:15:00",
                "end"    => "2023-11-21T05:45:00",
            ],
            [
                "source" => "Grzałka Elektryczna",
                "start"  => "2023-11-21T04:30:00",
                "end"    => "2023-11-21T06:00:00",
            ],
            [
                "source" => "Pompa Ciepła",
                "start"  => "2023-11-21T11:00:00",
                "end"    => "2023-11-21T14:20:00",
            ],
            [
                "source" => "Kocioł Gazowy",
                "start"  => "2023-11-21T18:00:00",
                "end"    => "2023-11-21T21:00:00",
            ],
        ]);

        $timeRange = $request->get('timeRange');
        $date      = $request->get('date');
        $location  = $request->get('location');
        $group     = $request->get('group');

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
