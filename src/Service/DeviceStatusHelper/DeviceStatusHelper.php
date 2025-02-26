<?php

namespace App\Service\DeviceStatusHelper;

use App\Entity\DeviceDailyStats;
use App\Entity\Hook;
use App\Model\DeviceStatus;
use App\Model\Status;
use App\Repository\HookRepository;
use Doctrine\Common\Collections\ArrayCollection;

abstract class DeviceStatusHelper
{
    /** @var array{Hook} */
    protected array $hooks;
    private int     $element = 0;

    public function __construct(
        protected readonly HookRepository $hookRepository,
    ) {}

    public function getHistory(int $elements = 2): ?ArrayCollection
    {
        $this->hooks ??= $this->hookRepository->findLastActiveByDevice($this::DEVICE_NAME);

        if (empty($this->hooks)) {
            return null;
        }

        $history = new ArrayCollection();

        for ($i = 0; $i < $elements; $i++) {
            $history->add($this->getStatus());

            $this->element++;
        }

        return $history;
    }

    public function getStatus(): ?DeviceStatus
    {
        $this->hooks ??= $this->hookRepository->findLastActiveByDevice($this::DEVICE_NAME);

        if (empty($this->hooks)) {
            return null;
        }

        return (new DeviceStatus())
            ->setStatus($this->isActive($this->hooks[$this->element]) ? Status::ACTIVE : Status::INACTIVE)
            ->setLastValue($this->hooks[$this->element]->getValue())
            ->setStatusDuration($this->getDeviceStatusUnchangedDuration())
        ;
    }

    public function getStatusHelperInstance(): static
    {
        return $this;
    }

    /**
     * Processes data from the sensor for the selected device on a given day
     *
     * @param string             $device
     * @param \DateTimeInterface $date
     * @return DeviceDailyStats
     * @throws \DateMalformedStringException
     */
    public function calculateDailyStats(string $device, \DateTimeInterface $date): DeviceDailyStats
    {
        $this->getDailyHooks($date);

        if (empty($this->hooks)) {
            throw new \RuntimeException(sprintf('No data to process for device %s in %s', $device, $date->format('Y-m-d')));
        }

        $dailyStats = new DeviceDailyStats($device, $date);

        $activeTime       = 0; // seconds
        $runTime          = 0; // seconds
        $longestRunTime   = 0; // seconds
        $pauseTime        = 0; // seconds
        $longestPauseTime = 0; // seconds
        $energy           = 0; // Ws
        $inclusions       = 0;

        for ($i = 0; $i < count($this->hooks); $i++) {
            $isActive = $this->isActive($this->hooks[$i]);
            $duration = $this->calculateHookDuration($this->hooks[$i], $this->hooks[$i + 1] ?? null);
            $energy   += $this->hooks[$i]->getValue() * $duration;

            if ($isActive) {
                if (
                    $i !== 0
                    && !$this->isActive($this->hooks[$i - 1])
                ) {
                    $inclusions++;
                }

                $pauseTime      = 0;
                $runTime        += $duration;
                $activeTime     += $duration;
                $longestRunTime = max($longestRunTime, $runTime);
            } else {
                $runTime          = 0;
                $pauseTime        += $duration;
                $longestPauseTime = max($longestPauseTime, $pauseTime);
            }
        }

        return $dailyStats
            ->setEnergy(round($energy / 3600, 1)) // Wh
            ->setInclusions($inclusions)
            ->setTotalActiveTime($activeTime)
            ->setLongestRunTime($longestRunTime)
            ->setLongestPauseTime($longestPauseTime)
        ;
    }

    /**
     * Calculates the duration (seconds) of a given measurement by comparing it with the next
     * If the next one is null, we calculate it until midnight, unless we calculate the statistics of the current day
     * Then we count to the current hour
     *
     * @param Hook  $current
     * @param ?Hook $next
     * @return int #seconds
     * @throws \DateMalformedStringException
     */
    private function calculateHookDuration(Hook $current, ?Hook $next): int
    {
        $today = new \DateTime('today');

        if (null !== $next) {
            // If the next hook is not a null, we calculate the difference between measures
            $interval = $current->getCreatedAt()->diff($next->getCreatedAt());
        } elseif ($current->getCreatedAt()->format('Y-z') === $today->format('Y-z')) {
            // If hook is from the day that is currently going on, we calculate the time to actual datetime
            $interval = $current->getCreatedAt()->diff(new \DateTime());
        } else {
            // calculate the time to the midnight of the measurement day
            $interval = $current->getCreatedAt()->diff(
                (new \DateTime($current->getCreatedAt()->format('Y-m-d')))
                    ->setTime(23, 59, 59)
            );
        }

        return $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    private function getDeviceStatusUnchangedDuration(): int
    {
        $reference = $this->element === 0 ? new \DateTime() : $this->hooks[$this->element - 1]->getCreatedAt();
        $interval  = $reference->diff($this->getFirstHookOfCurrentStatus()->getCreatedAt());

        return $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    /**
     * @param \DateTimeInterface $date
     * @return void
     * @throws \DateMalformedStringException
     */
    private function getDailyHooks(\DateTimeInterface $date): void
    {
        $this->hooks ??= $this->hookRepository->findHooksByDeviceAndDate($this::DEVICE_NAME, $date);

        if (count($this->hooks) === 0) {
            return;
        }

        if ($this->hooks[0]->getCreatedAt()->format('H:i:s') === '00:00:00') {
            return;
        }

        if (null !== $lastHookOfDayBefore = $this->hookRepository->findLastHookOfDay($this::DEVICE_NAME, (clone $date)->modify("-1 day"))) {
            $virtualFirstHook = clone $lastHookOfDayBefore;
            $virtualFirstHook->setCreatedAt(new \DateTimeImmutable((clone $date)->format('Y-m-d')));

            array_unshift($this->hooks, $virtualFirstHook);
        }
    }

    private function getFirstHookOfCurrentStatus(): Hook
    {
        $currentStatus = $this->isActive($this->hooks[$this->element]);

        for ($i = $this->element + 1; $i < count($this->hooks); $i++) {
            if ($currentStatus !== $this->isActive($this->hooks[$i])) {
                $this->element = $i - 1;

                return $this->hooks[$i - 1];
            }
        }

        throw new \RuntimeException('First hook of actual status not found');
    }
}
