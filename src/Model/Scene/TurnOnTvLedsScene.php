<?php

declare(strict_types=1);

namespace App\Model\Scene;

use App\Model\Device\Light\TvLedsBoard;
use App\Model\Device\Light\TvLedsCabinet;
use App\Model\Device\Light\TvLedsMonitor;

final class TurnOnTvLedsScene extends Scene
{
    public const ID = 1763315015654;
    public const NAME = 'turn.tv.leds.on';

    public function getActions(): array
    {
        return [
            SceneAction::on(TvLedsMonitor::NAME, 15),
            SceneAction::on(TvLedsBoard::NAME, 10),
            SceneAction::on(TvLedsCabinet::NAME, 5),
        ];
    }
}
