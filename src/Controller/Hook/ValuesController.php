<?php

namespace App\Controller\Hook;

use App\Entity\Hook;
use App\Repository\HookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ValuesController extends AbstractController
{
    #[Route('/list/{device}/{property}', name: 'app_hoke_values')]
    public function hook(string $device, string $property, HookRepository $repository): Response
    {
        $hooks = $repository->findByDeviceAndProperty($device, $property);

        return $this->json(array_map(function (Hook $hook) {
            return [
                'date'   => $hook->getCreatedAt()->format('Y-m-d H:i:s'),
                'value'  => number_format($hook->getValue(), 1),
            ];
        }, $hooks));
    }
}
