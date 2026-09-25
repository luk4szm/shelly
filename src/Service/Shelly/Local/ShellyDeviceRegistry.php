<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Model\Device\ShellyRpcDeviceInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ShellyDeviceRegistry
{
    /** @var array<string, ShellyRpcDeviceInterface> */
    private array $devices = [];

    /** @param iterable<ShellyRpcDeviceInterface> $devices */
    public function __construct(
        #[AutowireIterator('app.shelly.rpc_device')]
        iterable $devices,
    ) {
        foreach ($devices as $device) {
            $name = $device->getName();

            if (isset($this->devices[$name])) {
                throw new \LogicException(sprintf('Duplicate Shelly RPC device name "%s".', $name));
            }

            $this->devices[$name] = $device;
        }
    }

    public function getDevice(string $name): ShellyRpcDeviceInterface
    {
        return $this->devices[$name]
            ?? throw new \InvalidArgumentException(sprintf('Unknown Shelly RPC device "%s".', $name));
    }

    public function findByDeviceIdAndChannel(string $deviceId, int $channel): ?ShellyRpcDeviceInterface
    {
        $deviceId = strtolower($deviceId);
        $match    = null;

        foreach ($this->devices as $device) {
            if (strtolower($device->getDeviceId()) !== $deviceId || $device->getChannel() !== $channel) {
                continue;
            }

            if ($match !== null) {
                throw new \LogicException(sprintf(
                    'Multiple Shelly RPC devices match device ID "%s" and channel %d.',
                    $deviceId,
                    $channel,
                ));
            }

            $match = $device;
        }

        return $match;
    }

    /** @return list<string> */
    public function getDeviceNames(): array
    {
        $names = array_keys($this->devices);
        sort($names);

        return $names;
    }
}
