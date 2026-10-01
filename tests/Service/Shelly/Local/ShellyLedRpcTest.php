<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

use App\Exception\ShellyRpcException;
use App\Exception\ShellyWriteOutcomeUnknownException;
use App\Model\Device\Light\LightDevice;
use App\Model\Device\Light\KitchenLedsBottom;
use App\Model\Device\Light\KitchenLedsTop;
use App\Model\Device\Light\LocalWhiteLightDevice;
use App\Model\Device\Light\TvLedsBoard;
use App\Model\Device\Light\TvLedsCabinet;
use App\Model\Device\Light\TvLedsMonitor;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\Light\ShellyLightService;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyLedStatusReader;
use App\Service\Shelly\Local\ShellyLedWriter;
use App\Service\Shelly\Local\ShellyRpcClient;
use App\Service\Shelly\Local\ShellyRgbwWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellyLedRpcTest extends TestCase
{
    /**
     * @dataProvider ledChannels
     * @param class-string<LocalWhiteLightDevice> $deviceClass
     */
    public function testEachChannelUsesLocalRpcAndPreservesBrightness(
        string $deviceClass,
        string $host,
        int $channel,
        int $brightness,
    ): void {
        $device = new $deviceClass();
        $calls = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls, $device): MockResponse {
            $calls[] = [$method, $url, json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR)];

            return new MockResponse(count($calls) === 1
                ? json_encode(['id' => 1, 'result' => ['mac' => strtoupper($device->getDeviceId())]], JSON_THROW_ON_ERROR)
                : '{"id":1,"result":null}');
        });
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::never())->method('light');
        $rpcClient = new ShellyRpcClient($httpClient);
        $service = new ShellyLightService($cloud, new ShellyLedWriter($rpcClient), new ShellyRgbwWriter($rpcClient));

        self::assertSame([], $service->turnOn($device, white: $brightness));

        self::assertSame('POST', $calls[0][0]);
        self::assertSame('POST', $calls[1][0]);
        self::assertSame('http://' . $host . '/rpc', $calls[0][1]);
        self::assertSame($calls[0][1], $calls[1][1]);
        self::assertSame('Shelly.GetDeviceInfo', $calls[0][2]['method']);
        self::assertSame('Light.Set', $calls[1][2]['method']);
        self::assertSame(['id' => $channel, 'on' => true, 'brightness' => $brightness], $calls[1][2]['params']);
        self::assertSame(2, $httpClient->getRequestsCount());
    }

    /** @return iterable<string, array{class-string<LocalWhiteLightDevice>, string, int, int}> */
    public function ledChannels(): iterable
    {
        yield 'TV board' => [TvLedsBoard::class, 'shellyplusrgbwpm-ecc9ff4dc3f4.local', 0, 40];
        yield 'TV cabinet' => [TvLedsCabinet::class, 'shellyplusrgbwpm-ecc9ff4dc3f4.local', 1, 10];
        yield 'TV monitor' => [TvLedsMonitor::class, 'shellyplusrgbwpm-ecc9ff4dc3f4.local', 2, 60];
        yield 'kitchen top' => [KitchenLedsTop::class, 'shellyplusrgbwpm-2cbcbbc16fa4.local', 0, 65];
        yield 'kitchen bottom' => [KitchenLedsBottom::class, 'shellyplusrgbwpm-2cbcbbc16fa4.local', 1, 10];
    }

    public function testTurningOffOmitsBrightness(): void
    {
        $calls = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $calls[] = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);

            return new MockResponse(count($calls) === 1
                ? '{"id":1,"result":{"mac":"ECC9FF4DC3F4"}}'
                : '{"id":1,"result":null}');
        });
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::never())->method('light');
        $rpcClient = new ShellyRpcClient($httpClient);
        $service = new ShellyLightService($cloud, new ShellyLedWriter($rpcClient), new ShellyRgbwWriter($rpcClient));

        $service->turnOff(new TvLedsBoard());

        self::assertSame(['id' => 0, 'on' => false], $calls[1]['params']);
    }

    public function testUnconfiguredLightStillUsesCloud(): void
    {
        $httpClient = new MockHttpClient();
        $device = new class extends LightDevice {
            public const NAME = 'unconfigured-light';
            public const TYPE = 'rgbw';
            public const DEVICE_ID = 'unconfigured-device';
            public const CHANNEL = 0;
        };
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::once())
            ->method('light')
            ->with($device, 'on', 50, 5, [123, 244, 41])
            ->willReturn(['cloud' => true]);
        $rpcClient = new ShellyRpcClient($httpClient);
        $service = new ShellyLightService($cloud, new ShellyLedWriter($rpcClient), new ShellyRgbwWriter($rpcClient));

        self::assertSame(['cloud' => true], $service->turnOn($device, brightness: 50, white: 5, colors: [123, 244, 41]));
        self::assertSame(0, $httpClient->getRequestsCount());
    }

    public function testLedStatusIncludesCurrentBrightness(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('{"id":1,"result":{"id":2,"output":true,"brightness":15,"source":"HTTP_in"}}'));
        $reader = new ShellyLedStatusReader(new ShellyDeviceRegistry([new TvLedsMonitor()]), new ShellyRpcClient($httpClient));

        $status = $reader->read(TvLedsMonitor::NAME);

        self::assertSame('light:2', $status['component']);
        self::assertTrue($status['output']);
        self::assertSame(15, $status['brightness']);
    }

    public function testWrongFallbackMacPreventsWrite(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('', ['error' => 'mDNS unavailable']),
            new MockResponse('{"id":1,"result":{"mac":"000000000000"}}'),
        ]);

        try {
            (new ShellyRpcClient($httpClient))->setLedLight(new TvLedsMonitor(), true, 15);
            self::fail('Wrong device must not receive Light.Set.');
        } catch (ShellyRpcException $exception) {
            self::assertStringContainsString('identity mismatch', $exception->getMessage());
        }

        self::assertSame(2, $httpClient->getRequestsCount());
    }

    public function testWriteTimeoutDoesNotRetryAtFallbackIp(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('{"id":1,"result":{"mac":"ECC9FF4DC3F4"}}'),
            new MockResponse('', ['error' => 'Lost response after write']),
        ]);

        try {
            (new ShellyRpcClient($httpClient))->setLedLight(new TvLedsMonitor(), true, 15);
            self::fail('Unknown write outcome should be reported.');
        } catch (ShellyWriteOutcomeUnknownException $exception) {
            self::assertStringContainsString('was not retried', $exception->getMessage());
        }

        self::assertSame(2, $httpClient->getRequestsCount());
    }
}
