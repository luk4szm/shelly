<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Exception\ShellyRpcException;
use App\Model\Device\Cover\RollerCover;

final readonly class ShellyCoverStatusReader
{
    public function __construct(
        private ShellyDeviceRegistry $registry,
        private ShellyRpcClient      $rpcClient,
    ) {}

    /** @return array<string, mixed> */
    public function read(string $deviceName): array
    {
        $device = $this->registry->getDevice($deviceName);

        if (!$device instanceof RollerCover) {
            throw new ShellyRpcException(sprintf('Shelly device "%s" is not a cover.', $deviceName));
        }

        $rpcResult = $this->rpcClient->read($device, 'Cover.GetStatus', ['id' => $device->getChannel()]);
        $status    = $rpcResult->data;

        if (!isset($status['state']) || !in_array($status['state'], [
            'open', 'closed', 'opening', 'closing', 'stopped', 'calibrating',
        ], true)) {
            throw new ShellyRpcException(sprintf('Shelly cover "%s" returned no valid state.', $deviceName));
        }

        return [
            'device'           => $deviceName,
            'host'             => $device->getHostname(),
            'component'        => sprintf('cover:%d', $device->getChannel()),
            'endpoint'         => $rpcResult->endpoint,
            'connection'       => $rpcResult->connection,
            'response_time_ms' => $rpcResult->responseTimeMs,
            'total_time_ms'    => $rpcResult->totalTimeMs,
            'online'           => true,
            'state'            => $status['state'],
            'last_direction'   => in_array($status['last_direction'] ?? null, ['open', 'close'], true)
                ? $status['last_direction']
                : null,
            'pos_control'      => $status['pos_control'] ?? null,
            'current_pos'      => $status['current_pos'] ?? null,
            'source'           => $status['source'] ?? null,
        ];
    }
}
