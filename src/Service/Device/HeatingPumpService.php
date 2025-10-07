<?php

namespace App\Service\Device;

use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Service\Shelly\Switch\HeatingPumpsService;

class HeatingPumpService
{
    private ?Hook $stateHook;
    private ?Hook $powerHook;

    public function __construct(
        private readonly HookRepository      $hookRepository,
        private readonly HeatingPumpsService $heatingPumpsService,
    ) {}

    public function getActualState(string $pump): array
    {
        $this->stateHook = $this->hookRepository->findLastDeviceState($pump);
        $this->powerHook = $this->hookRepository->findLastDevicePowerHook($pump);

        return [
            'active' => $this->isPumpRunning(),
            'power'  => $this->powerHook ? $this->powerHook->getValue() : 0,
        ];
    }

    public function setHeatingPumpState(bool $enable): void
    {
        $enable ? $this->heatingPumpsService->turnOn() : $this->heatingPumpsService->turnOff();
    }

    protected function isPumpRunning(): bool
    {
        if (null === $this->stateHook) {
            return false;
        }

        return $this->stateHook->getValue() === 'on';
    }
}
