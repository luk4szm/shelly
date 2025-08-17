<?php

declare(strict_types=1);

namespace App\Controller\Cover;

use App\Service\Shelly\Cover\ShellyGarageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/garage', name: 'app_garage_')]
final class GarageController extends AbstractController
{
    #[Route('/move', name: 'move', methods: ['PATCH'])]
    public function index(Request $request, ShellyGarageService $garageService): Response
    {
        $direction = (string)$request->get('direction');

        switch ($direction) {
            case 'move':
                $garageService->move();
                break;

            case 'open':
                try {
                    $isOpen = $garageService->isOpen();
                } catch (\Exception $e) {
                    return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // If closed or unknown, trigger move to open
                if ($isOpen === false || $isOpen === null) {
                    // shelly has limit for cloud request
                    sleep(2);

                    $garageService->move();
                }
                break;

            case 'close':
                try {
                    $isOpen = $garageService->isOpen();
                } catch (\Exception $e) {
                    return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // If open or unknown, trigger move to close
                if ($isOpen === true || $isOpen === null) {
                    // shelly has limit for cloud request
                    sleep(2);

                    $garageService->move();
                }
                break;

            default:
                return $this->json(
                    sprintf("%s is not a valid direction", $direction),
                    Response::HTTP_BAD_REQUEST
                );
        }

        return $this->json([]);
    }

    #[Route('/read', name: 'read', methods: ['GET'])]
    public function read(ShellyGarageService $garageService): Response
    {
        try {
            $isOpen = $garageService->isOpen();
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'HTTP/1.1 429 Too Many Requests')) {
                return $this->json(['error' => 'Too many requests. Wait 1 second and try again.'], Response::HTTP_TOO_MANY_REQUESTS);
            }

            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['is_open' => $isOpen]);
    }
}
