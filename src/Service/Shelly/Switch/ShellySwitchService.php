<?php

namespace App\Service\Shelly\Switch;

use App\Service\Curl\Shelly\ShellyCloudCurlRequest;

readonly class ShellySwitchService
{
    public function __construct(
        private ShellyCloudCurlRequest $curlRequest,
    ) {
    }

    public function switch(string $deviceId, string $action): array
    {
        return $this->curlRequest->switch($deviceId, $action);
    }

    public function getStatus(string $deviceId): array
    {
        return $this->curlRequest->getStatus($deviceId);
    }
}
