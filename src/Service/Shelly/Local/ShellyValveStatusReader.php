<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Enum\ShellyComponentType;
use App\Exception\ShellyRpcException;
use App\Model\Device\Valve\ValveDevice;

final readonly class ShellyValveStatusReader
{
    public function __construct(
        private ShellyDeviceRegistry $registry,
        private ShellyRpcClient      $rpcClient,
    ) {}

    /** @return array<string, mixed> */
    public function read(string $deviceName): array
    {
        $device = $this->registry->getDevice($deviceName);

        if (!$device instanceof ValveDevice || $device->getComponentType() !== ShellyComponentType::Light) {
            throw new ShellyRpcException(sprintf('Shelly component "%s" is not a valve.', $deviceName));
        }

        $rpcResult = $this->rpcClient->read($device, 'Light.GetStatus', ['id' => $device->getChannel()]);
        $status    = $rpcResult->data;

        if (!isset($status['output']) || !is_bool($status['output'])) {
            throw new ShellyRpcException(sprintf('Shelly component "%s" returned no valid output state.', $deviceName));
        }

        return [
            'device'           => $deviceName,
            'host'             => $device->getHostname(),
            'component'        => sprintf('light:%d', $device->getChannel()),
            'endpoint'         => $rpcResult->endpoint,
            'connection'       => $rpcResult->connection,
            'response_time_ms' => $rpcResult->responseTimeMs,
            'total_time_ms'    => $rpcResult->totalTimeMs,
            'online'           => true,
            'output'           => $status['output'],
            'brightness'       => $status['brightness'] ?? null,
            'timer_started_at' => $status['timer_started_at'] ?? null,
            'timer_duration'   => $status['timer_duration'] ?? null,
            'source'           => $status['source'] ?? null,
        ];
    }
}
