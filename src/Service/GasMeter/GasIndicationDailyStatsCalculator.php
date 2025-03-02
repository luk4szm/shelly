<?php

namespace App\Service\GasMeter;

use App\Entity\GasMeter;
use App\Model\GasMeter\DailyConsumption;
use App\Repository\GasMeterRepository;
use App\Service\DailyStats\BoilerDailyStatsCalculator;

class GasIndicationDailyStatsCalculator
{
    private DailyConsumption $dailyConsumption;
    private \DateTime        $date;
    /** @var array{GasMeter} */
    private array $indications;

    public function __construct(
        private readonly BoilerDailyStatsCalculator $boilerDailyStatsCalculator,
        private readonly GasMeterRepository         $gasRepository,
    ) {
    }

    public function getDailyConsumption($date): DailyConsumption
    {
        $this->date        = $date;
        $this->indications ??= $this->getFullDayIndications();
        $boilerDailyStats  = $this->boilerDailyStatsCalculator->calculateDailyStats($this->date);

        $dailyConsumption = (new DailyConsumption())
            ->setConsumption(end($this->indications)->getIndication() - $this->indications[0]->getIndication())
            ->setBoilerInclusions($boilerDailyStats->getInclusions())
            ->setBoilerActiveTime($boilerDailyStats->getTotalActiveTime())
            ->setEnergyUsed($boilerDailyStats->getEnergy());

        return $this->dailyConsumption = $dailyConsumption;
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
            $startIndication = $this->interpolateIndicationLinear($previous, $dayIndications[0] ?: $next, $this->date);
        } elseif (count($dayIndications) > 1 || (count($dayIndications) == 1 && $next instanceof GasMeter)) {
            $startIndication = $this->extrapolateIndicationLinear($dayIndications[0], $dayIndications[1] ?: $next, $this->date);
        } else {
            throw new \Exception(sprintf('Cannot interpolate start indication for %s', $this->date->format('Y-m-d')));
        }

        if (
            $next instanceof GasMeter
            && (!empty($dayIndications) || $previous instanceof GasMeter)
        ) {
            $endIndication = $this->interpolateIndicationLinear(
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

    private function interpolateIndicationLinear(
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
        return $this->interpolateIndicationLinear($indicationFrom, $indicationTo, $targetDateTime);
    }
}
