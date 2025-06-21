<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DailyController extends AbstractController
{
    #[Route('/daily/{date}')]
    public function index(string $date = null): Response
    {
        $date = $date ? new \DateTime($date) : new \DateTime();

        return $this->render('front/daily/index.html.twig', [
            'date' => $date->format('Y-m-d'),
        ]);
    }
}
