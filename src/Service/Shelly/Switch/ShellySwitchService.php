<?php

namespace App\Service\Shelly\Switch;

use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\Local\ShellySwitchWriter;
use App\Service\Shelly\ShellyDeviceService;

readonly class ShellySwitchService extends ShellyDeviceService
{
    public function __construct(
        ShellyCloudCurlRequest $curlRequest,
        private ?ShellySwitchWriter $localSwitchWriter = null,
    ) {
        parent::__construct($curlRequest);
    }

    public function switch(string $deviceId, int $channel, string $action): array
    {
        if ($this->localSwitchWriter !== null) {
            $localResult = $this->localSwitchWriter->setIfConfigured(
                $deviceId,
                $channel,
                $action === 'on',
            );

            if ($localResult !== null) {
                return $localResult->data;
            }
        }

        return $this->curlRequest->switch($deviceId, $channel, $action);
    }

    public function switchGroup(array $deviceIds, string $action): array
    {
        return $this->curlRequest->switchGroup($deviceIds, $action);
    }

    public function getStatus(string $deviceId): array
    {
        return $this->curlRequest->getStatus($deviceId);
    }
}
