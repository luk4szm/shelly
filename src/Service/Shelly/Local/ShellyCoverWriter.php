<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Exception\ShellyRpcException;
use App\Model\Device\Cover\RollerCover;
use App\Model\Shelly\RpcResult;

final readonly class ShellyCoverWriter
{
    public function __construct(
        private ShellyDeviceRegistry $registry,
        private ShellyRpcClient      $rpcClient,
    ) {}

    public function set(string $deviceName, string $action): RpcResult
    {
        $device = $this->registry->getDevice($deviceName);

        if (!$device instanceof RollerCover) {
            throw new ShellyRpcException(sprintf('Shelly device "%s" is not a cover.', $deviceName));
        }

        return $this->rpcClient->setCover($device, $action);
    }
}
