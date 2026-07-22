<?php

namespace App\Service\DailyStats;

use App\Entity\DeviceDailyStats;
use App\Entity\Hook;
use App\Model\Device\DeviceInterface;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use App\Utils\Hook\HookDurationUtil;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

abstract class DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        #[AutowireIterator('app.shelly.device_status_helper')]
        private readonly iterable $statusHelpers,
        private readonly HookRepository $hookRepository,
    ) {}

    public function supports(string $device): bool
    {
        return $device === $this->getDeviceName();
    }

    public function isDeviceInstalledOn(\DateTimeInterface $date): bool
    {
        return new \DateTimeImmutable($this->getDevice()::INSTALLATION_DATE) <= $date;
    }

    /**
     * Processes data from the sensor for the selected device on a given day
     *
     * @param \DateTimeInterface $date
     * @return DeviceDailyStats
     * @throws \DateMalformedStringException
     * @throws \RuntimeException
     */
    public function calculateDailyStats(\DateTimeInterface $date): DeviceDailyStats
    {
        $hooks      = $this->getDailyHooks($date);
        $dailyStats = new DeviceDailyStats($this->getDeviceName(), $date);

        if (empty($hooks)) {
            return $dailyStats;
        }

        $statusHelper = $this->getStatusHelper();

        $activeTime       = 0; // seconds
        $runTime          = 0; // seconds
        $longestRunTime   = 0; // seconds
        $pauseTime        = 0; // seconds
        $longestPauseTime = 0; // seconds
        $energy           = 0; // Ws
        $inclusions       = 0;
        $firstSeenAt      = null;
        $lastSeenAt       = null;

        for ($i = 0; $i < count($hooks); $i++) {
            $isActive = $statusHelper->isActive($hooks[$i]);
            $duration = HookDurationUtil::calculateHookDuration($hooks[$i], $hooks[$i + 1] ?? null);

            if ($isActive) {
                if ($firstSeenAt === null) {
                    $firstSeenAt = $hooks[$i]->getCreatedAt();
                }

                $lastSeenAt = $hooks[$i]->getCreatedAt();

                if (
                    $i !== 0
                    && !$statusHelper->isActive($hooks[$i - 1])
                ) {
                    $inclusions++;
                }

                $pauseTime      = 0;
                $runTime        += $duration;
                $activeTime     += $duration;
                $energy         += $hooks[$i]->getValue() * $duration;
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
            ->setFirstSeenAt($firstSeenAt)
            ->setLastSeenAt($lastSeenAt)
        ;
    }

    /**
     * @param \DateTimeInterface $date
     * @return Hook[]
     * @throws \DateMalformedStringException
     */
    private function getDailyHooks(\DateTimeInterface $date): array
    {
        $hooks = $this->hookRepository->findHooksByDeviceAndDate($this->getDeviceName(), $date);

        if (count($hooks) === 0) {
            return [];
        }

        if ($hooks[0]->getCreatedAt()->format('H:i:s') === '00:00:00') {
            return $hooks;
        }

        if (null !== $lastHookOfDayBefore = $this->hookRepository->findPreviousHookToDate($this->getDeviceName(), $date)) {
            $virtualFirstHook = clone $lastHookOfDayBefore;
            $virtualFirstHook->setCreatedAt(new \DateTimeImmutable((clone $date)->format('Y-m-d')));

            array_unshift($hooks, $virtualFirstHook);
        }

        return $hooks;
    }

    public function getDeviceName(): string
    {
        return $this->getDevice()::NAME;
    }

    private function getStatusHelper(): DeviceStatusHelperInterface
    {
        foreach ($this->statusHelpers as $statusHelper) {
            if ($statusHelper->supports($this->getDeviceName())) {
                return $statusHelper;
            }
        }

        throw new \RuntimeException(sprintf('There is no configured status helper for the device called "%s"', $this->getDeviceName()));
    }

    /**
     * @return class-string<DeviceInterface>
     */
    abstract protected function getDevice(): string;
}
