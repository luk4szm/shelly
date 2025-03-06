<?php

namespace App\Controller\Front;

use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_front_dashboard')]
    public function index(
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable $statusHelpers,
    ): Response
    {
        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            $devices[$helper->getDeviceName()] = $helper->getHistory(2);
        }

        return $this->render('front/dashboard/index.html.twig', [
            'devices' => $devices ?? [],
        ]);
    }
}
