<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Scene;
use App\Repository\SceneRepository;
use App\Service\Shelly\Scene\ShellySceneService;
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

    #[Route('/run/{id}', name: 'run', methods: ['PATCH'])]
    public function run(Scene $scene, ShellySceneService $sceneService): Response
    {
        $sceneService->trigger((string)$scene->getShellyId());

        return $this->json([]);
    }
}
