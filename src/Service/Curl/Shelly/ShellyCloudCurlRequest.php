<?php

namespace App\Service\Curl\Shelly;

use App\Service\Curl\Curl;

class ShellyCloudCurlRequest extends Curl
{
    private const URL    = 'https://shelly-164-eu.shelly.cloud/v2/devices/api';
    private const METHOD = 'POST';

    /**
     * @param string             $deviceId
     * @param string{"on"|"off"} $action
     * @return array
     */
    public function switch(string $deviceId, string $action): array
    {
        return $this->request(
            self::METHOD,
            sprintf("%s/set/switch?auth_key=%s", self::URL, $_ENV['SHELLY_AUTH_KEY']),
            json: [
                "id"      => $deviceId,
                "channel" => 0,
                "on"      => $action === 'on',
            ]
        );
    }

    /**
     * @param string $deviceId
     * @return array
     */
    public function getStatus(string $deviceId): array
    {
        return $this->request(
            self::METHOD,
            sprintf("%s/get?auth_key=%s", self::URL, $_ENV['SHELLY_AUTH_KEY']),
            [
                "ids"    => [$deviceId],
                "select" => ["status"],
//                "pick"   => ["status" => ["temperature:102"]],
            ]
        );
    }
}
