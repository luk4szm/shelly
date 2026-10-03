<?php

namespace App\Model\Scene;

use App\Model\Device\Relay\DrivewayLights;
use App\Model\Device\Relay\Garland;

final class TurnOnOutsideLightsScene extends Scene
{
    public const ID   = 1788212050480;
    public const NAME = 'turn.outside.lights.on';

    public function getActions(): array
    {
        return [
            SceneAction::on(Garland::NAME),
            SceneAction::on(DrivewayLights::NAME),
        ];
    }
}
