<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\HeatingNote;
use App\Form\HeatingNoteType;
use App\Model\DateRange;
use App\Repository\HeatingNoteRepository;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use App\Service\Location\LocationFinder;
use App\Utils\HeatingNoteSerializer;
use App\Utils\Hook\GraphHandler\TemperatureGraphHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/heating', name: 'app_front_heating_')]
final class HeatingController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, HeatingNoteRepository $noteRepository): Response
    {
        $form = $this->createForm(HeatingNoteType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $note = $form->getData();
            $note->setCreatedBy($this->getUser());

            $noteRepository->save($note);

            $this->addFlash('success', 'Note saved successfully.');

            return $this->redirectToRoute('app_front_heating_index');
        }

        return $this->render('front/heating/index.html.twig', [
            'date'  => $request->get('date'),
            'form'  => $form->createView(),
            'notes' => $noteRepository->findForDate(new \DateTime()),
        ]);
    }

    #[Route('/get-data/{date}', name: 'get_data')]
    public function getData(
        LocationFinder                                                  $locationFinder,
        HookRepository                                                  $hookRepository,
        HeatingNoteRepository                                           $noteRepository,
        TemperatureGraphHandler                                         $graphHandler,
        #[AutowireIterator('app.shelly.device_status_helper')] iterable $statusHelpers,
        string                                                          $date = '',
    ): Response {
        $from      = (new \DateTime($date))->setTime(0, 0);
        $to        = (clone $from)->setTime(23, 59, 59);
        $isToday   = $from->format('Y-m-d') === (new \DateTime())->format('Y-m-d');
        $locations = $locationFinder->getLocations('heating');

        $currentDayHooks = $hookRepository->findLocationTemperatures(
            $from,
            (clone $to)->modify("+1 second"),
            $locations,
        );

        $previousDayHooks = $hookRepository->findLocationTemperatures(
            (clone $from)->modify("-1 day"),
            (clone $to)->modify("-1 day"),
            $locations,
        );

        // Prepare activities (active intervals) for the three devices
        $dateRange   = new DateRange(clone $from, clone $to);
        $activities  = [];

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($statusHelpers as $helper) {
            if (!$helper->showOnDashboard()) {
                continue;
            }

            // Get grouped history so we have consecutive running/standby blocks
            $history = $helper->getHistory(dateRange: $dateRange, grouped: true);
            if ($history === null) {
                continue;
            }

            $deviceName = $helper->getDeviceName();
            $activities[$deviceName] = [];

            foreach ($history as $group) {
                // each group is an array-like with keys 'running' and/or 'standby'
                if (!isset($group['running'])) {
                    continue;
                }

                $status = $group['running'];
                $start  = $status->getStartTime();
                $end    = $status->getEndTime();

                if ($start === null) {
                    continue;
                }

                // Bounds safety
                if ($start < $from) {
                    $start = clone $from;
                }

                if ($end === null) {
                    $end = $isToday ? new \DateTime() : clone $to;
                } elseif ($end > $to) {
                    $end = clone $to;
                }

                $activities[$deviceName][] = [
                    'from' => $start->format(DATE_ATOM),
                    'to'   => $end->format(DATE_ATOM),
                ];
            }

            // Remove empty arrays to keep payload compact
            if (empty($activities[$deviceName])) {
                unset($activities[$deviceName]);
            }
        }

        $notes = $noteRepository->findForDate($from);

        return $this->json([
            'currentDay'  => empty($currentDayHooks) ? [] : $graphHandler->prepareGroupedHooks($currentDayHooks),
            'previousDay' => empty($previousDayHooks) ? [] : $graphHandler->prepareGroupedHooks($previousDayHooks),
            'activities'  => $activities,
            'notes'       => [
                'data'     => array_map(function (HeatingNote $note) {
                    return HeatingNoteSerializer::serialize($note);
                }, $notes),
                'rendered' => $this->renderView('front/heating/_partials/heating_notes.html.twig', [
                    'notes' => $notes,
                ])
            ],
        ]);
    }
}
