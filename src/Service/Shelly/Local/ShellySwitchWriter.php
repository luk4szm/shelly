<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Enum\ShellyComponentType;
use App\Exception\ShellyRpcException;
use App\Model\Shelly\RpcResult;

final readonly class ShellySwitchWriter
{
    public function __construct(
        private ShellyDeviceRegistry $registry,
        private ShellyRpcClient      $rpcClient,
    ) {}

    public function set(string $deviceName, bool $on): RpcResult
    {
        $device = $this->registry->getDevice($deviceName);

        if ($device->getComponentType() !== ShellyComponentType::Switch) {
            throw new ShellyRpcException(sprintf(
                'Shelly device "%s" is not a switch.',
                $deviceName,
            ));
        }

        return $this->rpcClient->setSwitch($device, $on);
    }

    public function setIfConfigured(string $deviceId, int $channel, bool $on): ?RpcResult
    {
        $device = $this->registry->findByDeviceIdAndChannel($deviceId, $channel);

        return $device === null ? null : $this->set($device->getName(), $on);
    }
}
