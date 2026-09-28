<?php

namespace App\Service\Shelly\Light;

use App\Model\Device\Light\LightDevice;
use App\Model\Device\Light\LocalWhiteLightDevice;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\Local\ShellyLedWriter;
use App\Service\Shelly\ShellyDeviceService;

readonly class ShellyLightService extends ShellyDeviceService
{
    public function __construct(
        ShellyCloudCurlRequest  $curlRequest,
        private ShellyLedWriter $ledWriter,
    ) {
        parent::__construct($curlRequest);
    }

    public function turnOn(LightDevice $device, int $brightness = null, int $white = null, array $colors = []): array
    {
        if ($device instanceof LocalWhiteLightDevice) {
            if ($colors !== []) {
                throw new \InvalidArgumentException('RGB colors cannot be set on a white LED channel.');
            }

            return $this->ledWriter->turnOn($device, $white)->data;
        }

        return $this->curlRequest->light($device, 'on', $brightness, $white, $colors);
    }

    public function turnOff(LightDevice $device): array
    {
        if ($device instanceof LocalWhiteLightDevice) {
            return $this->ledWriter->turnOff($device)->data;
        }

        return $this->curlRequest->light($device, 'off');
    }
}
