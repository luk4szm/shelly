<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

use App\Model\Device\Relay\FireplacePump;
use App\Model\Device\Relay\Garland;
use App\Model\Device\Relay\HeatingPumpReturn;
use App\Model\Device\Relay\HeatingPumpSupply;
use App\Model\Device\Relay\HotWaterPump;
use App\Model\Device\Relay\Hydrophore;
use App\Model\Device\ShellyRpcDeviceInterface;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyRpcClient;
use App\Service\Shelly\Local\ShellySwitchWriter;
use App\Service\Shelly\Switch\ShellySwitchService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellySwitchRoutingTest extends TestCase
{
    /**
     * @dataProvider configuredPumpProvider
     * @param class-string<ShellyRpcDeviceInterface> $deviceClass
     */
    public function testConfiguredPumpUsesLocalRpcWithoutCallingCloud(
        string $deviceClass,
        string $expectedHost,
        int $expectedChannel,
    ): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($expectedHost, $expectedChannel): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame(sprintf('http://%s/rpc', $expectedHost), $url);
            self::assertJsonStringEqualsJsonString(
                json_encode([
                    'id' => 1,
                    'method' => 'Switch.Set',
                    'params' => ['id' => $expectedChannel, 'on' => true],
                ], JSON_THROW_ON_ERROR),
                $options['body'],
            );

            return new MockResponse('{"id":1,"result":{"was_on":false}}');
        });
        $device = new $deviceClass();
        $registry = new ShellyDeviceRegistry([$device, new Garland()]);
        $writer = new ShellySwitchWriter($registry, new ShellyRpcClient($httpClient));
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::never())->method('switch');

        $result = (new ShellySwitchService($cloud, $writer))->switch(
            $device->getDeviceId(),
            $device->getChannel(),
            'on',
        );

        self::assertSame(['was_on' => false], $result);
        self::assertSame(1, $httpClient->getRequestsCount());
    }

    /** @return iterable<string, array{class-string<ShellyRpcDeviceInterface>, string, int}> */
    public function configuredPumpProvider(): iterable
    {
        yield 'fireplace' => [FireplacePump::class, 'shellyplus1pm-cc7b5c8378b4.local', 0];
        yield 'hydrophore' => [Hydrophore::class, 'shellyplus1pm-fce8c0fd0a7c.local', 0];
        yield 'hot water' => [HotWaterPump::class, 'shellyplus1pm-64b708097270.local', 0];
        yield 'heating supply' => [HeatingPumpSupply::class, 'shellyplus2pm-ecc9ff4b35e4.local', 0];
        yield 'heating return' => [HeatingPumpReturn::class, 'shellyplus2pm-ecc9ff4b35e4.local', 1];
    }

    public function testUnconfiguredDeviceRetainsCloudFallback(): void
    {
        $httpClient = new MockHttpClient();
        $registry = new ShellyDeviceRegistry([new FireplacePump()]);
        $writer = new ShellySwitchWriter($registry, new ShellyRpcClient($httpClient));
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::once())
            ->method('switch')
            ->with('unconfigured-device', 0, 'off')
            ->willReturn(['cloud' => true]);

        $result = (new ShellySwitchService($cloud, $writer))->switch('unconfigured-device', 0, 'off');

        self::assertSame(['cloud' => true], $result);
        self::assertSame(0, $httpClient->getRequestsCount());
    }
}
