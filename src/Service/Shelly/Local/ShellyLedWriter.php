<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Model\Device\Light\LocalWhiteLightDevice;
use App\Model\Shelly\RpcResult;

final readonly class ShellyLedWriter
{
    public function __construct(
        private ShellyRpcClient $rpcClient,
    ) {}

    public function turnOn(LocalWhiteLightDevice $device, ?int $brightness): RpcResult
    {
        return $this->rpcClient->setLedLight($device, true, $brightness);
    }

    public function turnOff(LocalWhiteLightDevice $device): RpcResult
    {
        return $this->rpcClient->setLedLight($device, false);
    }
}
