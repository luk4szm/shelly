<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Scene;
use App\Enum\InsolationLevel;
use App\Repository\ConfigRepository;
use App\Repository\SceneRepository;
use App\Service\AirQuality\InsolationService;
use App\Service\Shelly\Scene\ShellySceneService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/scene', name: 'app_front_scene_')]
final class SceneController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(SceneRepository $repository): Response
    {
        return $this->render('front/scene/index.html.twig', [
//            'form'        => $gasMeterForm->createView(),
            'scenes' => $repository->findAll(),
        ]);
    }

    #[Route('/run/{shellyId}', name: 'run', methods: ['PATCH'])]
    public function run(
        #[MapEntity(mapping: ['shellyId' => 'shellyId'])] Scene $scene,
        ShellySceneService                                      $sceneService,
    ): Response
    {
        $sceneService->trigger((string)$scene->getShellyId());

        return $this->json([]);
    }

    #[Route('/lighting-check/{group}', name: 'lighting_check', requirements: ['group' => 'inside|outside'], methods: ['GET'])]
    public function lightingCheck(
        string            $group,
        ConfigRepository  $configRepository,
        InsolationService $insolationService,
    ): Response
    {
        $configName = sprintf('auto_light_%s', $group);
        $threshold  = $group === 'outside' ? InsolationLevel::OutdoorLightsOn : InsolationLevel::IndoorLightsOn;
        $insolation = $insolationService->getActualInsolation();
        $enabled    = $configRepository->getValueByName($configName) === '1';

        return $this->json([
            'enabled'        => $enabled,
            'insolation'     => $insolation,
            'threshold'      => $threshold->value,
            'should_turn_on' => $enabled && $insolation < $threshold->value,
        ]);
    }

    #[Route('/modal', name: 'modal', methods: ['GET'])]
    public function modal(ConfigRepository $configRepository): Response
    {
        return $this->json([
            'modal_content' => $this->renderView('front/scene/modal_content.html.twig', [
                'config' => $configRepository->getAllValues(),
            ]),
        ]);
    }
}
