<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Form\GasMeterIndicationType;
use App\Repository\GasMeterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GasMeterController extends AbstractController
{
    #[Route('/gas-meter', name: 'app_front_gas_meter')]
    public function index(
        GasMeterRepository $repository,
    ): Response
    {
        $indications  = $repository->findPreviousWithOffset();
        $gasMeterForm = $this->createForm(GasMeterIndicationType::class, options: [
            'lastIndication' => $indications[0],
        ]);

        return $this->render('front/gas_meter/index.html.twig', [
            'form'        => $gasMeterForm->createView(),
            'indications' => $indications,
        ]);
    }
}
