<?php

declare(strict_types=1);

namespace App\Controller\Config;

use App\Model\Request\ConfigRequestPayload;
use App\Repository\ConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/config', name: 'app_config_')]
class ConfigController extends AbstractController
{
    #[Route('/set', name: 'set', methods: ['PATCH'])]
    public function set(
        #[MapRequestPayload] ConfigRequestPayload $payload,
        ConfigRepository                          $configRepository,
    ): Response
    {
        $configRepository->updateValueByName($payload->name, $payload->value);

        return $this->json([]);
    }
}
