<?php

namespace App\Service\Curl\Shelly;

use App\Service\Curl\Curl;

class ShellyCloudCurlRequest extends Curl
{
    private const URL    = 'https://shelly-164-eu.shelly.cloud/v2/devices/api';
    private const METHOD = 'POST';

    /**
     * @param string             $deviceId
     * @param int                $channel
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
     * @@deprecated  something is wrong with this request. The verse is correct, but the device does not turn on.
     * Use individual requests instead of the following
     *
     * @see https://shelly-api-docs.shelly.cloud/cloud-control-api/communication-v2#control-device-groups
     * @param array  $deviceIds List of <ID>_<CHANNEL> (channel defaults to 0 if not included)
     * @param string $action
     * @return array
     */
    public function switchGroup(array $deviceIds, string $action): array
    {
        return $this->request(
            self::METHOD,
            sprintf("%s/set/groups?auth_key=%s", self::URL, $_ENV['SHELLY_AUTH_KEY']),
            json: [
                "ids"     => $deviceIds,
                "command" => [
                    "on" => $action === 'on',
//                    "toggle_after" => 5; // After how many seconds, the state should be set to opposite the value of "on"
                ],
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
     * @param string $mode
     * @param int    $channel
     * @param int    $brightness
     * @return array
     */
    public function light(string $deviceId, string $mode, int $channel, int $brightness = 0): array
    {
        return $this->request(
            self::METHOD,
            sprintf("%s/set/light?auth_key=%s", self::URL, $_ENV['SHELLY_AUTH_KEY']),
            json: [
                "id"         => $deviceId,
                "channel"    => $channel,
                "on"         => $mode === 'on',
                "brightness" => $brightness,
//                "toggle_after" => 5, // Number of seconds before stopping the position change
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

    /**
     * @param string $sceneId
     * @return array
     */
    public function scene(string $sceneId): array
    {
        return $this->request(
            self::METHOD,
            sprintf("%s/scene/manual_run?auth_key=%s", str_replace('/v2/devices/api', '', self::URL), $_ENV['SHELLY_AUTH_KEY']),
            [
                "id" => $sceneId,
            ]
        );
    }
}
