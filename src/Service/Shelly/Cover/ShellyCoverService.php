<?php

namespace App\Service\Shelly\Cover;

use App\Service\Curl\Shelly\ShellyCloudCurlRequest;

readonly class ShellyCoverService
{
    private const SHELLY_DEVICE_ID = '2CBCBB2DC408';

    public function __construct(
        private ShellyCloudCurlRequest $curlRequest,
    ) {
    }

    public function open(): array
    {
        return $this->curlRequest->cover(self::SHELLY_DEVICE_ID, 'open');
    }

    public function close(): array
    {
        return $this->curlRequest->cover(self::SHELLY_DEVICE_ID, 'close');
    }

    public function stop(): array
    {
        return $this->curlRequest->cover(self::SHELLY_DEVICE_ID, 'stop');
    }
}
