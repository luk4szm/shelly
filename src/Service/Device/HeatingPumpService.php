<?php

namespace App\Service\Device;

use App\Entity\Hook;
use App\Repository\HookRepository;

class HeatingPumpService
{
    private ?Hook  $stateHook;
    private ?Hook  $powerHook;

    public function __construct(
        private readonly HookRepository $hookRepository
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

    protected function isPumpRunning(): bool
    {
        if (null === $this->stateHook) {
            return false;
        }

        return $this->stateHook->getValue() === 'on';
    }
}
