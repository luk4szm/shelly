<?php

namespace App\Service\Device;

use App\Entity\Hook;
use App\Model\Device\Fireplace;
use App\Model\Device\FireplacePump;
use App\Repository\HookRepository;
use App\Service\Shelly\Switch\FireplacePumpsService;

class FireplacePumpService
{
    private ?Hook $powerHook;

    public function __construct(
        private readonly HookRepository        $hookRepository,
        private readonly FireplacePumpsService $fireplacePumpsService,
    ) {}

    public function getActualState(): array
    {
        $this->powerHook = $this->hookRepository->findLastDevicePowerHook(Fireplace::NAME);

        return [
            'active' => $this->isPumpRunning(),
            'power'  => $this->powerHook ? $this->powerHook->getValue() : 0,
        ];
    }

    public function setHeatingPumpState(bool $enable): void
    {
        $enable ? $this->fireplacePumpsService->turnOn() : $this->fireplacePumpsService->turnOff();
    }

    protected function isPumpRunning(): bool
    {
        if (null === $this->powerHook) {
            return false;
        }

        return $this->powerHook->getValue() > FireplacePump::BOUNDARY_POWER;
    }
}
