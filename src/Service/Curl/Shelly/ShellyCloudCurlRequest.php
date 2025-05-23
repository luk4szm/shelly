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
    public function switch(string $deviceId, int $channel, string $action): array
    {
        return $this->request(
            self::METHOD,
            sprintf("%s/set/switch?auth_key=%s", self::URL, $_ENV['SHELLY_AUTH_KEY']),
            json: [
                "id"      => $deviceId,
                "channel" => $channel,
                "on"      => $action === 'on',
//                "toggle_after": 5; // After how many seconds, the state should be set to opposite the value of "on"
            ]
        );
    }

    /**
     * @param string                        $deviceId
     * @param string{"open"|"close"|"stop"} $position
     * @return array
     */
    public function cover(string $deviceId, string $position): array
    {
        return $this->request(
            self::METHOD,
            sprintf("%s/set/cover?auth_key=%s", self::URL, $_ENV['SHELLY_AUTH_KEY']),
            json: [
                "id"       => $deviceId,
                "channel"  => 0,
                "position" => $position,
//                "duration" => 5, // Number of seconds before stopping the position change
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
