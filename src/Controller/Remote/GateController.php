<?php

declare(strict_types=1);

namespace App\Controller\Remote;

use App\Entity\UserNotification;
use App\Enum\Channel;
use App\Enum\Event\SuplaGateOpenEvent;
use App\Repository\UserNotificationRepository;
use App\Service\Gate\SuplaGateOpener;
use App\Service\LogReader\GateLogReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/remote', name: 'app_remote_')]
class GateController extends AbstractController
{
    #[Route('/gate', name: 'gate')]
    public function gate(SuplaGateOpener $gateOpener, GateLogReader $logReader): Response
    {
        $gateResponse = $gateOpener->read();

        if (!isset($gateResponse['connected']) || $gateResponse['connected'] !== true) {
            throw new \RuntimeException('Gate is disconnected.');
        }

        return $this->render('remote/gate.html.twig', [
            'is_open'    => !($gateResponse['hi'] === true),
            'parsedLogs' => $logReader->getParsedLogs(),
        ]);
    }

    #[Route('/gate/enable-notification', name: 'gate_enable_notification')]
    public function saveNotification(
        Request                    $request,
        UserNotificationRepository $notificationRepository
    ): Response
    {
        $notify = (new UserNotification())
            ->setChannel(Channel::from($request->query->get('channel')))
            ->setUser($this->getUser())
            ->setEvent(SuplaGateOpenEvent::OPEN->value);

        $notificationRepository->save($notify);

        $this->addFlash('success', 'Notification saved.');

        return $this->redirectToRoute('app_remote_gate');
    }
}
