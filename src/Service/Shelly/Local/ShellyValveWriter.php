<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Model\Device\Valve\ValveDevice;
use App\Model\Shelly\RpcResult;

/** The Shelly valve controller is based on a 4-channel controller for LED lights */
final readonly class ShellyValveWriter
{
    public function __construct(
        private ShellyRpcClient $rpcClient,
    ) {}

    public function openValve(ValveDevice $device, int $durationSeconds): RpcResult
    {
        return $this->rpcClient->setValve($device, true, $durationSeconds);
    }

    public function closeValve(ValveDevice $device): RpcResult
    {
        return $this->rpcClient->setValve($device, false);
    }
}
