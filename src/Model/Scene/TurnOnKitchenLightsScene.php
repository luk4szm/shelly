<?php

declare(strict_types=1);

namespace App\Model\Scene;

use App\Model\Device\Light\KitchenLedsBottom;
use App\Model\Device\Light\KitchenLedsTop;

final class TurnOnKitchenLightsScene extends Scene
{
    public const ID = 1763313824622;
    public const NAME = 'turn.kitchen.lights.on';

    public function getActions(): array
    {
        return [
            SceneAction::on(KitchenLedsTop::NAME, 65),
            SceneAction::on(KitchenLedsBottom::NAME, 10),
        ];
    }
}
