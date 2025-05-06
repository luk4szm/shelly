<?php

namespace App\Controller\Front;

use App\Form\GasMeterIndicationType;
use App\Model\Location\Location;
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
//        $locations              = $locationFinder->getLocations();
        $locations              = array_merge(Location::getHeatingLocations(), ['salon']);

        $gasMeterForm = $this->createForm(GasMeterIndicationType::class, options: [
            'lastIndication' => $lastGasMeterIndication->getIndication(),
        ]);

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            $devices[$helper->getDeviceName()] = $helper->getHistory(2);
        }

        return $this->render('front/dashboard/index.html.twig', [
            'devices'                => $devices ?? [],
            'locations'              => $locations,
            'gasMeterForm'           => $gasMeterForm->createView(),
            'lastGasMeterIndication' => $lastGasMeterIndication,
            'temperatures'           => $hookRepository->findActualTemps($locations),
        ]);
    }

    #[Route('/error500', name: 'app_front_error_500')]
    public function error(): Response
    {
        throw new \Exception('test error 500');
    }
}
