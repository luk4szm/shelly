<?php

declare(strict_types=1);

namespace App\Model\Scene;

use App\Model\Device\Light\TvLedsBoard;
use App\Model\Device\Light\TvLedsCabinet;
use App\Model\Device\Light\TvLedsMonitor;

final class TurnOffTvLedsScene extends Scene
{
    public const ID = 1763315145292;
    public const NAME = 'turn.tv.leds.off';

    public function getActions(): array
    {
        return [
            SceneAction::off(TvLedsMonitor::NAME),
            SceneAction::off(TvLedsBoard::NAME),
            SceneAction::off(TvLedsCabinet::NAME),
        ];
    }
}
