<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Enum\ShellyComponentType;
use App\Exception\ShellyRpcException;
use App\Model\Shelly\SwitchStatus;

final readonly class ShellySwitchStatusReader
{
    public function __construct(
        private ShellyDeviceRegistry $registry,
        private ShellyRpcClient $rpcClient,
    ) {
    }

    public function read(string $componentName): SwitchStatus
    {
        $device = $this->registry->getDevice($componentName);

        if ($device->getComponentType() !== ShellyComponentType::Switch) {
            throw new ShellyRpcException(sprintf(
                'Shelly component "%s" is not a switch.',
                $componentName,
            ));
        }

        $rpcResult = $this->rpcClient->read(
            $device,
            'Switch.GetStatus',
            ['id' => $device->getChannel()],
        );
        $result = $rpcResult->data;

        if (!array_key_exists('output', $result)) {
            throw new ShellyRpcException(sprintf(
                'Shelly component "%s" returned no output state.',
                $componentName,
            ));
        }

        return new SwitchStatus(
            $device,
            (bool) $result['output'],
            isset($result['apower']) ? (float) $result['apower'] : null,
            isset($result['voltage']) ? (float) $result['voltage'] : null,
            isset($result['current']) ? (float) $result['current'] : null,
            isset($result['source']) ? (string) $result['source'] : null,
            $rpcResult->endpoint,
            $rpcResult->connection,
        );
    }
}
