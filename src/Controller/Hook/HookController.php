<?php

namespace App\Controller\Hook;

use App\Entity\Hook;
use App\Repository\HookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HookController extends AbstractController
{
    #[Route('/hook/{device}/{property}/{value}', name: 'app_hoke_save')]
    public function hook(Request $request, string $device, string $property, string $value, HookRepository $repository): Response
    {
        $hook = new Hook($device, $property, $value);

        $repository->save($hook);

        // temp
        if (in_array($device, ['salon', 'sypialnia', 'stas', 'dziewczynki'], true)) {
            mail('lukasz@mikowski.pl', 'shelly h&t report', $request->getContent());
        }

        return $this->json($hook);
    }
}
