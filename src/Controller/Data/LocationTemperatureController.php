<?php

declare(strict_types=1);

namespace App\Controller\Data;

use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Utils\Hook\HookGrouper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocationTemperatureController extends AbstractController
{
    #[Route('/data/temp/{location}', name: 'app_data_location_temperature_index', methods: ['GET'])]
    public function index(HookRepository $hookRepository, string $location = null): Response
    {
        $hooks = $hookRepository->findLocationTemperatures($location);

        if ($location === null) {
            foreach (HookGrouper::byDevice($hooks) as $location => $data) {
                $grouped[$location] = array_map(static function (Hook $hook) {
                    return [
                        'datetime' => $hook->getCreatedAt()->format('Y-m-d H:i:s'),
                        'value'    => (float)$hook->getValue(),
                    ];
                }, $data);
            }

            return $this->json($grouped ?? []);
        }

        return $this->json(
            array_map(function (Hook $hook) {
                return [
                    'datetime' => $hook->getCreatedAt()->format('Y-m-d H:i:s'),
                    'value'    => (float)$hook->getValue(),
                ];
            }, $hooks)
        );
    }
}
