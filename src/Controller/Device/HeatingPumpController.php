<?php

declare(strict_types=1);

namespace App\Controller\Device;

use App\Repository\Process\ProcessRepository;
use App\Service\Device\HeatingPumpService;
use App\Service\Processable\Condition\Fireplace\FireplaceOffCondition;
use App\Service\Processable\Condition\Fireplace\FireplaceOnCondition;
use App\Service\Processable\Creators\HeatingPumpProcessCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HeatingPumpController extends AbstractController
{
    #[Route('/device/heating-pump', name: 'app_device_heating_pump_index', methods: ['POST'])]
    public function index(
        Request                   $request,
        HeatingPumpService        $heatingPumpService,
        HeatingPumpProcessCreator $processCreator,
        ProcessRepository         $processRepository,
    ): Response
    {
        $heatingStartOn           = $request->request->get('heating_start_on');
        $heatingEndOn             = $request->request->get('heating_end_on');
        $heatingAction            = $request->request->get('heating_action');
        $heatingActionConditional = $request->request->get('heating_action_conditional');

        if (!$heatingStartOn && !$heatingEndOn && $heatingAction !== null) {
            // start/stop heating pump immediately
            $heatingPumpService->setHeatingPumpState($heatingAction === 'turn_on');

            $this->addFlash('success', 'Heating pump state updated');

            return $this->redirectToRoute('app_front_dashboard');
        }

        if ($heatingStartOn) {
            $startProcess = $processCreator->create(true, new \DateTimeImmutable($heatingStartOn));

            $processRepository->save($startProcess);
        }

        if ($heatingEndOn) {
            $endProcess = $processCreator->create(false, new \DateTimeImmutable($heatingEndOn));

            $processRepository->save($endProcess);
        }

        if ($heatingActionConditional) {
            $fireplaceProcess = match ($heatingActionConditional) {
                FireplaceOnCondition::NAME  => $processCreator->create(true, new \DateTimeImmutable(), [FireplaceOnCondition::NAME]),
                FireplaceOffCondition::NAME => $processCreator->create(false, new \DateTimeImmutable(), [FireplaceOffCondition::NAME]),
            };

            $processRepository->save($fireplaceProcess);
        }

        return $this->redirectToRoute('app_front_dashboard');
    }
}
