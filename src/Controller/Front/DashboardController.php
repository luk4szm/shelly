<?php

namespace App\Controller\Front;

use App\Form\GasMeterIndicationType;
use App\Repository\GasMeterRepository;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use App\Service\Location\LocationFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_front_dashboard')]
    public function index(
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable           $statusHelpers,
        LocationFinder     $locationFinder,
        GasMeterRepository $gasMeterRepository,
        HookRepository     $hookRepository,
    ): Response
    {
        $lastGasMeterIndication = $gasMeterRepository->findLast();
        $locations              = array_merge($locationFinder->getLocations('buffer'), $locationFinder->getLocations('rooms'));

        $gasMeterForm = $this->createForm(GasMeterIndicationType::class, options: [
            'lastIndication' => $lastGasMeterIndication->getIndication(),
        ]);

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            $devices[] = [
                'name'     => $helper->getDeviceName(),
                'deviceId' => $helper->getDeviceId(),
                'history'  => $helper->getHistory(2),
            ];
        }

        return $this->render('front/dashboard/index.html.twig', [
            'devices'                => $devices ?? [],
            'locations'              => $locations,
            'gasMeterForm'           => $gasMeterForm->createView(),
            'lastGasMeterIndication' => $lastGasMeterIndication,
            'temperatures'           => array_values($hookRepository->findActualTemps($locations)),
        ]);
    }
}
