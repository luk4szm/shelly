<?php

namespace App\Controller\GasMeter;

use App\Form\GasMeterIndicationType;
use App\Repository\GasMeterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GasMeterFormController extends AbstractController
{
    #[Route('/gas/meter/indication/save', name: 'app_gas_meter_form')]
    public function index(Request $request, GasMeterRepository $repository): Response
    {
        $form = $this->createForm(GasMeterIndicationType::class, options: [
            'lastIndication' => $repository->findLast()->getIndication(),
        ])->handleRequest($request);

        if ($form->isSubmitted() === false) {
            throw new \Exception('This controller only supports the completed new company contact form');
        }

        if ($form->isValid() === false) {
            $this->addFlash('error', 'Niepoprawna wartość odczytu');

            return $this->redirectToRoute('app_front_gas_meter');
        }

        $repository->save($form->getData());

        $this->addFlash('success', 'Zapisano nowy odczyt');

        return $this->redirectToRoute('app_front_gas_meter');
    }
}
