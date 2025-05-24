<?php

namespace App\Service\Shelly;

use App\Service\Curl\Shelly\ShellyCloudCurlRequest;

readonly abstract class ShellyDeviceService
{
    public function __construct(
        protected ShellyCloudCurlRequest $curlRequest,
    ) {
    }

    public function getStatus(string $deviceId): array
    {
        return $this->curlRequest->getStatus($deviceId);
    }

}
