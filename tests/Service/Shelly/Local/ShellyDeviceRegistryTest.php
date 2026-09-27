<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

use App\Model\Device\Relay\DrivewayLights;
use App\Model\Device\Relay\Garland;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use PHPUnit\Framework\TestCase;

final class ShellyDeviceRegistryTest extends TestCase
{
    public function testItMapsAComponentToItsPhysicalDevice(): void
    {
        $registry = new ShellyDeviceRegistry([new Garland()]);

        $device = $registry->getDevice('girlanda');

        self::assertSame(1, $device->getChannel());
        self::assertSame('ShellyPlus2PM-345F45193B80.local', $device->getHostname());
        self::assertSame(['girlanda'], $registry->getDeviceNames());
    }

    public function testItMatchesDrivewayLightsOnlyOnItsOwnChannel(): void
    {
        $drivewayLights = new DrivewayLights();
        $registry = new ShellyDeviceRegistry([new Garland(), $drivewayLights]);

        self::assertSame($drivewayLights, $registry->findByDeviceIdAndChannel('B0B21C103C08', 0));
        self::assertNull($registry->findByDeviceIdAndChannel('b0b21c103c08', 1));
        self::assertSame('ShellyPlus1PM-B0B21C103C08.local', $drivewayLights->getHostname());
        self::assertSame('192.168.1.195', $drivewayLights->getFallbackIp());
        self::assertNull($drivewayLights->getProfile());
    }
}
