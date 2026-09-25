<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

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
}
