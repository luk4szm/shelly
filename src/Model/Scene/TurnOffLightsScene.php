<?php

namespace App\Model\Scene;

use App\Model\Device\Light\KitchenLedsBottom;
use App\Model\Device\Light\KitchenLedsTop;
use App\Model\Device\Light\TvLedsBoard;
use App\Model\Device\Light\TvLedsCabinet;
use App\Model\Device\Light\TvLedsMonitor;
use App\Model\Device\Relay\DrivewayLights;
use App\Model\Device\Relay\GardenHalogen;
use App\Model\Device\Relay\Garland;

final class TurnOffLightsScene extends Scene
{
    public const ID   = 1776464366415;
    public const NAME = 'turn.lights.off';

    public function getActions(): array
    {
        return [
            SceneAction::off(KitchenLedsTop::NAME),
            SceneAction::off(KitchenLedsBottom::NAME),
            SceneAction::off(TvLedsMonitor::NAME),
            SceneAction::off(TvLedsBoard::NAME),
            SceneAction::off(TvLedsCabinet::NAME),
            SceneAction::off(Garland::NAME),
            SceneAction::off(DrivewayLights::NAME),
            SceneAction::off(GardenHalogen::NAME),
        ];
    }
}
