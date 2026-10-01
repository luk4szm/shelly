<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Model\Device\Light\LocalRgbwLightDevice;
use App\Model\Shelly\RpcResult;

final readonly class ShellyRgbwWriter
{
    public function __construct(
        private ShellyRpcClient $rpcClient,
    ) {}

    /** @param list<int> $colors */
    public function turnOn(
        LocalRgbwLightDevice $device,
        ?int                 $brightness,
        ?int                 $white,
        array                $colors,
    ): RpcResult
    {
        return $this->rpcClient->setRgbw($device, true, $brightness, $white, $colors);
    }

    public function turnOff(LocalRgbwLightDevice $device): RpcResult
    {
        return $this->rpcClient->setRgbw($device, false);
    }
}
