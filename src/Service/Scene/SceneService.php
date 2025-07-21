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
        $this->gateOpener->open();

        sleep(3);

        if ($this->garageService->isOpen()) {
            $this->garageService->move();
        }

        sleep(3);

        $this->coverService->close();
    }

    public function comingHouse(): void
    {
        $this->gateOpener->open();

        sleep(3);

        if (!$this->garageService->isOpen()) {
            $this->garageService->move();
        }

        sleep(3);

        $this->coverService->open();
    }
}
