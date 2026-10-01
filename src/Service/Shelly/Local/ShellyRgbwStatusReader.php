<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Exception\ShellyRpcException;
use App\Model\Device\Light\LocalRgbwLightDevice;

final readonly class ShellyRgbwStatusReader
{
    public function __construct(
        private ShellyDeviceRegistry $registry,
        private ShellyRpcClient $rpcClient,
    ) {
    }

    /** @return array<string, mixed> */
    public function read(string $deviceName): array
    {
        $device = $this->registry->getDevice($deviceName);

        if (!$device instanceof LocalRgbwLightDevice) {
            throw new ShellyRpcException(sprintf('Shelly component "%s" is not an RGBW LED strip.', $deviceName));
        }

        $result = $this->rpcClient->read($device, 'RGBW.GetStatus', ['id' => $device->getChannel()]);
        $status = $result->data;

        if (!isset($status['output']) || !is_bool($status['output'])) {
            throw new ShellyRpcException(sprintf('Shelly RGBW strip "%s" returned no valid output state.', $deviceName));
        }

        return [
            'device' => $deviceName,
            'host' => $device->getHostname(),
            'component' => sprintf('rgbw:%d', $device->getChannel()),
            'endpoint' => $result->endpoint,
            'connection' => $result->connection,
            'response_time_ms' => $result->responseTimeMs,
            'total_time_ms' => $result->totalTimeMs,
            'online' => true,
            'output' => $status['output'],
            'brightness' => $status['brightness'] ?? null,
            'rgb' => $status['rgb'] ?? null,
            'white' => $status['white'] ?? null,
            'source' => $status['source'] ?? null,
        ];
    }
}
