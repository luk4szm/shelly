<?php

namespace App\Controller\Hook;

use App\Entity\Hook;
use App\Repository\HookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HookController extends AbstractController
{
    #[Route('/hook/{device}/{property}/{value}', name: 'app_hoke_save')]
    public function hook(string $device, string $property, string $value, HookRepository $repository): Response
    {
        $hook = new Hook($device, $property, $value);

        $repository->save($hook);

        // temp
        if ($device === 'sypialnia') {
            mail('lukasz@mikowski.pl', 'shelly h&t report', $value);
        }

        return $this->json($hook);
    }
}
