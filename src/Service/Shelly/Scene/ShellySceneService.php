<?php

namespace App\Service\Shelly\Scene;

use App\Service\Shelly\ShellyDeviceService;

readonly class ShellySceneService extends ShellyDeviceService
{
    public function trigger(string $sceneId): array
    {
        return $this->curlRequest->scene($sceneId);
    }
}
