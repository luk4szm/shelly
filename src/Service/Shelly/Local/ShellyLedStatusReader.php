<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Exception\ShellyRpcException;
use App\Model\Device\Light\LocalWhiteLightDevice;

final readonly class ShellyLedStatusReader
{
    public function __construct(
        private ShellyDeviceRegistry $registry,
        private ShellyRpcClient      $rpcClient,
    ) {}

    /** @return array<string, mixed> */
    public function read(string $deviceName): array
    {
        $device = $this->registry->getDevice($deviceName);

        if (!$device instanceof LocalWhiteLightDevice) {
            throw new ShellyRpcException(sprintf('Shelly component "%s" is not a configured LED channel.', $deviceName));
        }

        $result = $this->rpcClient->read($device, 'Light.GetStatus', ['id' => $device->getChannel()]);
        $status = $result->data;

        if (!isset($status['output']) || !is_bool($status['output'])) {
            throw new ShellyRpcException(sprintf('Shelly LED "%s" returned no valid output state.', $deviceName));
        }

        return [
            'device'           => $deviceName,
            'host'             => $device->getHostname(),
            'component'        => sprintf('light:%d', $device->getChannel()),
            'endpoint'         => $result->endpoint,
            'connection'       => $result->connection,
            'response_time_ms' => $result->responseTimeMs,
            'total_time_ms'    => $result->totalTimeMs,
            'online'           => true,
            'output'           => $status['output'],
            'brightness'       => $status['brightness'] ?? null,
            'source'           => $status['source'] ?? null,
        ];
    }
}
