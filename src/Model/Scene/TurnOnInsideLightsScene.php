<?php

namespace App\Model\Scene;

use App\Model\Device\Light\KitchenLedsBottom;
use App\Model\Device\Light\KitchenLedsTop;
use App\Model\Device\Light\TvLedsBoard;
use App\Model\Device\Light\TvLedsCabinet;
use App\Model\Device\Light\TvLedsMonitor;

final class TurnOnInsideLightsScene extends Scene
{
    public const ID   = 1789077304598;
    public const NAME = 'turn.inside.lights.on';

    public function getActions(): array
    {
        return [
            SceneAction::on(KitchenLedsBottom::NAME, 10),
            SceneAction::on(KitchenLedsTop::NAME, 65),
            SceneAction::on(TvLedsBoard::NAME, 10),
            SceneAction::on(TvLedsCabinet::NAME, 5),
            SceneAction::on(TvLedsMonitor::NAME, 15),
        ];
    }
}
