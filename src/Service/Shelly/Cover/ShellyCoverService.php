<?php

namespace App\Service\Shelly\Cover;

use App\Service\Shelly\ShellyDeviceService;

readonly class ShellyCoverService extends ShellyDeviceService
{
    private const SHELLY_DEVICE_ID = '2CBCBB2DC408';

    public function open(): array
    {
        $this->curlRequest->cover(self::SHELLY_DEVICE_ID, 'open');

        sleep(25);

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
