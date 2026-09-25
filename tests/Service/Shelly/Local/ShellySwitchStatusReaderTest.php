<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

use App\Model\Device\Relay\Garland;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyRpcClient;
use App\Service\Shelly\Local\ShellySwitchStatusReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellySwitchStatusReaderTest extends TestCase
{
    public function testItMapsTheRpcResultToASwitchStatus(): void
    {
        $registry = new ShellyDeviceRegistry([new Garland()]);
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'id' => 1,
            'result' => [
                'id' => 1,
                'source' => 'HTTP_in',
                'output' => true,
                'apower' => 24.5,
                'voltage' => 218.9,
                'current' => 0.115,
            ],
        ], JSON_THROW_ON_ERROR)));
        $reader = new ShellySwitchStatusReader($registry, new ShellyRpcClient($httpClient));

        self::assertSame([
            'device' => 'girlanda',
            'host' => 'ShellyPlus2PM-345F45193B80.local',
            'component' => 'switch:1',
            'endpoint' => 'ShellyPlus2PM-345F45193B80.local',
            'connection' => 'mdns',
            'online' => true,
            'output' => true,
            'power' => 24.5,
            'voltage' => 218.9,
            'current' => 0.115,
            'source' => 'HTTP_in',
        ], $reader->read('girlanda')->toArray());
    }
}
