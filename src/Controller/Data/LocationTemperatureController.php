<?php

declare(strict_types=1);

namespace App\Controller\Data;

use App\Entity\Hook;
use App\Repository\HookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocationTemperatureController extends AbstractController
{
    #[Route('/data/temp/{location}', name: 'app_data_location_temperature_index', methods: ['GET'])]
    public function index(string $location, HookRepository $hookRepository): Response
    {
        $hooks = $hookRepository->findLocationTemperatures($location, new \DateTime("-12 hours"));

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
