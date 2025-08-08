<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Form\GasMeterIndicationType;
use App\Model\DateRange;
use App\Model\Device\Boiler;
use App\Repository\GasMeterRepository;
use App\Service\DeviceStatus\BoilerDeviceStatusHelper;
use App\Model\DeviceStatus;
use App\Model\Status;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GasMeterController extends AbstractController
{
    #[Route('/gas-meter', name: 'app_front_gas_meter')]
    public function index(
        GasMeterRepository       $repository,
        BoilerDeviceStatusHelper $boilerStatusHelper,
    ): Response
    {
        $indications  = $repository->findPreviousWithOffset();
        $gasMeterForm = $this->createForm(GasMeterIndicationType::class, options: [
            'lastIndication' => $indications[0]->getIndication(),
        ]);

        // Calculate estimated gas consumption between indications based on boiler active energy usage
        $estimated = [];
        $cumulative = 0.0;

        foreach ($indications as $index => $current) {
            $estInterval = null;

            if (isset($indications[$index + 1])) {
                $previous = $indications[$index + 1];
                $dateRange = new DateRange($previous->getCreatedAt(), $current->getCreatedAt());
                $history = $boilerStatusHelper->getHistory(dateRange: $dateRange);

                if ($history !== null && !$history->isEmpty()) {
                    $usedEnergyWh = 0.0;

                    // Sum only ACTIVE statuses usedEnergy
                    foreach ($history as $deviceStatus) {
                        /* @var DeviceStatus $deviceStatus */
                        if ($deviceStatus->getStatus() === Status::ACTIVE) {
                            $usedEnergyWh += $deviceStatus->getUsedEnergy();
                        }
                    }

                    $estInterval = $usedEnergyWh * Boiler::EST_FUEL_CONSUME;
                    $cumulative += $estInterval;
                }
            }

            $estimated[$index] = [
                'interval'   => $estInterval,        // may be null for the last row
                'cumulative' => $cumulative ?: null, // null for the first row where no interval yet
            ];
        }

        return $this->render('front/gas_meter/index.html.twig', [
            'form'        => $gasMeterForm->createView(),
            'indications' => $indications,
            'estimated'   => $estimated,
        ]);
    }
}
