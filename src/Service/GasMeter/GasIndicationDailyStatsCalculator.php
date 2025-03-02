<?php

namespace App\Service\GasMeter;

use App\Entity\GasMeter;
use App\Model\GasMeter\DailyConsumption;
use App\Repository\GasMeterRepository;
//use App\Repository\HookRepository;

class GasIndicationDailyStatsCalculator
{
    private DailyConsumption $dailyConsumption;
    private \DateTime        $date;
    /** @var array{GasMeter} */
    private array $indications;

    public function __construct(
//        private readonly HookRepository     $hookRepository,
        private readonly GasMeterRepository $gasRepository,
    ) {
    }

    public function getDailyConsumption($date): DailyConsumption
    {
        $this->date             = $date;
        $this->dailyConsumption = new DailyConsumption();
        $this->indications      ??= $this->getFullDayIndications();

        $this->dailyConsumption->setConsumption(
            round(end($this->indications)->getIndication() - $this->indications[0]->getIndication(), 3)
        );

        return $this->dailyConsumption;
    }

    private function getFullDayIndications(): array
    {
        $dayIndications = $this->gasRepository->findForDate($this->date);
        $previous       = $this->gasRepository->findFirstPreviousToDate($this->date);
        $next           = $this->gasRepository->findFirstNextToDate($this->date);

        if (
            $previous instanceof GasMeter
            && (!empty($dayIndications) || $next instanceof GasMeter)
        ) {
            $startIndication = $this->interpoleIndicationLinear($previous, $dayIndications[0] ?: $next, $this->date);
        } elseif (count($dayIndications) > 1 || (count($dayIndications) == 1 && $next instanceof GasMeter)) {
            $startIndication = $this->extrapolateIndicationLinear($dayIndications[0], $dayIndications[1] ?: $next, $this->date);
        } else {
            throw new \Exception(sprintf('Cannot interpolate start indication for %s', $this->date->format('Y-m-d')));
        }

        if (
            $next instanceof GasMeter
            && (!empty($dayIndications) || $previous instanceof GasMeter)
        ) {
            $endIndication = $this->interpoleIndicationLinear(
                end($dayIndications) ?: $previous,
                $next,
                (clone $this->date)->setTime(23, 59, 59)
            );
        } elseif (count($dayIndications) > 1 || (count($dayIndications) == 1 && $previous instanceof GasMeter)) {
            $endIndication = $this->extrapolateIndicationLinear(
                $dayIndications[count($dayIndications) - 2] ?? end($dayIndications),
                end($dayIndications) ?: $next,
                (clone $this->date)->setTime(23, 59, 59)
            );
        } else {
            throw new \Exception(sprintf('Cannot interpolate end indication for %s', $this->date->format('Y-m-d')));
        }

        return array_merge(
            [(new GasMeter($startIndication))->setCreatedAt((clone $this->date)->setTime(0, 0))],
            $dayIndications,
            [(new GasMeter($endIndication))->setCreatedAt((clone $this->date)->setTime(23, 59, 59))],
        );
    }

    private function interpoleIndicationLinear(
        GasMeter  $indicationFrom,
        GasMeter  $indicationTo,
        \DateTime $targetDateTime,
    ): float {
        $timeDiff       = $indicationTo->getCreatedAt()->getTimestamp() - $indicationFrom->getCreatedAt()->getTimestamp();
        $timeToTarget   = $targetDateTime->getTimestamp() - $indicationFrom->getCreatedAt()->getTimestamp();
        $indicationDiff = $indicationTo->getIndication() - $indicationFrom->getIndication();
        $factor         = $timeToTarget / $timeDiff;

        return round($indicationFrom->getIndication() + $indicationDiff * $factor, 3);
    }

    private function extrapolateIndicationLinear(
        GasMeter  $indicationFrom,
        GasMeter  $indicationTo,
        \DateTime $targetDateTime,
    ): float {
        return $this->interpoleIndicationLinear($indicationFrom, $indicationTo, $targetDateTime);
    }
}
