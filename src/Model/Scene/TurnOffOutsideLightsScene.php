<?php

namespace App\Model\Scene;

use App\Model\Device\Relay\DrivewayLights;
use App\Model\Device\Relay\Garland;

final class TurnOffOutsideLightsScene extends Scene
{
    public const ID   = 1788212019830;
    public const NAME = 'turn.outside.lights.off';

    public function getActions(): array
    {
        return [
            SceneAction::off(Garland::NAME),
            SceneAction::off(DrivewayLights::NAME),
        ];
    }
}
