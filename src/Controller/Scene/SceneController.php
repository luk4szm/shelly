<?php

declare(strict_types=1);

namespace App\Controller\Scene;

use App\Service\Scene\SceneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** @deprecated  */
class SceneController extends AbstractController
{
    #[Route('/scene', name: 'app_scene_index', methods: ['PATCH'])]
    public function index(Request $request, SceneService $sceneService): Response
    {
        match ($request->get('direction')) {
            'leaving' => $sceneService->leavingHouse(),
            'coming'  => $sceneService->comingHouse(),
        };

        return $this->json([]);
    }
}
