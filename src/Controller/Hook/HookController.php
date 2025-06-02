<?php

namespace App\Controller\Hook;

use App\Entity\Hook;
use App\Repository\HookRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HookController extends AbstractController
{
    #[Route('/hook/cover/{direction}', name: 'app_hoke_cover_save')]
    public function cover(string $direction, LoggerInterface $coverControllerLogger): Response
    {
        $message = match ($direction) {
            'open'  => 'Covers have been opened',
            'close' => 'Covers have been closed',
            default => throw new \InvalidArgumentException('Invalid cover direction'),
        };

        $coverControllerLogger->info($message, ['device' => 'switch']);

        return $this->json([]);
    }
    #[Route('/hook/{device}/{property}/{value}', name: 'app_hoke_save')]
    public function hook(string $device, string $property, string $value, HookRepository $repository): Response
    {
        $hook = new Hook($device, $property, $value);

        $repository->save($hook);

        return $this->json($hook);
    }
}
