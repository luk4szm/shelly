<?php

namespace App\Model\Scene;

use App\Model\Device\Light\KitchenLedsBottom;
use App\Model\Device\Light\KitchenLedsTop;

final class TurnOffKitchenLightsScene extends Scene
{
    public const ID   = 1763313930468;
    public const NAME = 'turn.kitchen.lights.off';

    public function getActions(): array
    {
        return [
            SceneAction::off(KitchenLedsTop::NAME),
            SceneAction::off(KitchenLedsBottom::NAME),
        ];
    }
}
