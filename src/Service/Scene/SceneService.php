<?php

namespace App\Service\Scene;

use App\Service\Gate\SuplaGateOpener;
use App\Service\Shelly\Cover\ShellyCoverService;
use App\Service\Shelly\Cover\ShellyGarageService;

readonly class SceneService
{
    public function __construct(
        private SuplaGateOpener     $gateOpener,
        private ShellyCoverService  $coverService,
        private ShellyGarageService $garageService,
    ) {
    }

    public function leavingHouse(): void
    {
        $this->coverService->close();

        if ($this->garageService->isOpen()) {
            $this->garageService->move();
        }

        $this->gateOpener->open();
    }

    public function comingHouse(): void
    {
        $this->gateOpener->open();

        if (!$this->garageService->isOpen()) {
            $this->garageService->move();
        }

        $this->coverService->open();
    }
}
