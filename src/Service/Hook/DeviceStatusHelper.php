<?php

namespace App\Service\Hook;

use App\Entity\Hook;
use App\Model\DeviceStatus;
use App\Model\Status;
use App\Repository\HookRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DeviceStatusHelper
{
    private const BOUNDARY_POWER_CENTRAL_HEATING = 8;

    /** @var array{Hook} */
    private array $hooks;
    private int   $element = 0;

    public function __construct(
        private readonly HookRepository $hookRepository,
    ) {}

    public function getHistory(string $device, int $elements = 2): ?ArrayCollection
    {
        $this->hooks ??= $this->hookRepository->findLastActiveByDevice($device);

        if (empty($this->hooks)) {
            return null;
        }

        $history = new ArrayCollection();

        for ($i = 0; $i < $elements; $i++) {
            $history->add($this->getStatus($device));

            $this->element++;
        }

        return $history;
    }

    public function getStatus(string $device): ?DeviceStatus
    {
        $this->hooks ??= $this->hookRepository->findLastActiveByDevice($device);

        if (empty($this->hooks)) {
            return null;
        }

        return (new DeviceStatus())
            ->setStatus($this->isActive('piec', $this->hooks[$this->element]) ? Status::ACTIVE : Status::INACTIVE)
            ->setStatusDuration($this->getDeviceStatusUnchangedDuration())
            ->setLastValue($this->hooks[$this->element]->getValue())
        ;
    }

    public function isActive(string $device, Hook $hook): bool
    {
        return match ($device) {
            'piec'  => (float)$hook->getValue() > self::BOUNDARY_POWER_CENTRAL_HEATING,
            default => throw new \InvalidArgumentException("Invalid device {$device}"),
        };
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
    public function calculateHookDuration(Hook $current, ?Hook $next): int
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

    private function getDeviceStatusUnchangedDuration(): float
    {
        $reference = $this->element === 0 ? new \DateTime() : $this->hooks[$this->element - 1]->getCreatedAt();
        $firstHook = $this->getFirstHookOfCurrentStatus();
        $interval  = $reference->diff($firstHook->getCreatedAt());

        return $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    private function getFirstHookOfCurrentStatus(): Hook
    {
        $currentStatus = $this->isActive('piec', $this->hooks[$this->element]);

        for ($i = $this->element + 1; $i < count($this->hooks); $i++) {
            if ($currentStatus !== $this->isActive('piec', $this->hooks[$i])) {
                $this->element = $i - 1;

                return $this->hooks[$i - 1];
            }
        }

        throw new \RuntimeException('First hook of actual status not found');
    }
}
