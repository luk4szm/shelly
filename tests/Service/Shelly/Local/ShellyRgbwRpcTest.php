<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

use App\Exception\ShellyRpcException;
use App\Exception\ShellyWriteOutcomeUnknownException;
use App\Model\Device\Light\BedLeds;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\Light\ShellyLightService;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyLedWriter;
use App\Service\Shelly\Local\ShellyRgbwStatusReader;
use App\Service\Shelly\Local\ShellyRgbwWriter;
use App\Service\Shelly\Local\ShellyRpcClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellyRgbwRpcTest extends TestCase
{
    public function testScheduledWhiteOnlyLightDoesNotRestoreStoredRgbColor(): void
    {
        $calls = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $calls[] = [$method, $url, json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR)];

            return new MockResponse(count($calls) === 1
                ? '{"id":1,"result":{"mac":"9451DC0AB154"}}'
                : '{"id":1,"result":null}');
        });
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::never())->method('light');
        $rpc = new ShellyRpcClient($http);
        $service = new ShellyLightService($cloud, new ShellyLedWriter($rpc), new ShellyRgbwWriter($rpc));

        self::assertSame([], $service->turnOn(new BedLeds(), white: 5));

        self::assertSame('POST', $calls[0][0]);
        self::assertSame('http://shellyplusrgbwpm-9451dc0ab154.local/rpc', $calls[0][1]);
        self::assertSame($calls[0][1], $calls[1][1]);
        self::assertSame('Shelly.GetDeviceInfo', $calls[0][2]['method']);
        self::assertSame('RGBW.Set', $calls[1][2]['method']);
        self::assertSame([
            'id' => 0,
            'on' => true,
            'rgb' => [0, 0, 0],
            'white' => 5,
        ], $calls[1][2]['params']);
        self::assertSame(2, $http->getRequestsCount());
    }

    public function testColoredCommandPreservesRgbBrightnessAndWhite(): void
    {
        $calls = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $calls[] = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);

            return new MockResponse(count($calls) === 1
                ? '{"id":1,"result":{"mac":"9451DC0AB154"}}'
                : '{"id":1,"result":null}');
        });
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::never())->method('light');
        $rpc = new ShellyRpcClient($http);
        $service = new ShellyLightService($cloud, new ShellyLedWriter($rpc), new ShellyRgbwWriter($rpc));

        $service->turnOn(new BedLeds(), brightness: 50, white: 50, colors: [123, 244, 41]);

        self::assertSame([
            'id' => 0,
            'on' => true,
            'rgb' => [123, 244, 41],
            'brightness' => 50,
            'white' => 50,
        ], $calls[1]['params']);
    }

    public function testOffDoesNotChangeColorParameters(): void
    {
        $calls = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $calls[] = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);

            return new MockResponse(count($calls) === 1
                ? '{"id":1,"result":{"mac":"9451DC0AB154"}}'
                : '{"id":1,"result":null}');
        });
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::never())->method('light');
        $rpc = new ShellyRpcClient($http);
        $service = new ShellyLightService($cloud, new ShellyLedWriter($rpc), new ShellyRgbwWriter($rpc));

        $service->turnOff(new BedLeds());

        self::assertSame(['id' => 0, 'on' => false], $calls[1]['params']);
    }

    public function testReadStatusMapsRgbwFields(): void
    {
        $http = new MockHttpClient(new MockResponse('{"id":1,"result":{"id":0,"output":true,"brightness":0,"rgb":[131,255,0],"white":5,"source":"SHC"}}'));
        $reader = new ShellyRgbwStatusReader(new ShellyDeviceRegistry([new BedLeds()]), new ShellyRpcClient($http));

        $status = $reader->read(BedLeds::NAME);

        self::assertSame('rgbw:0', $status['component']);
        self::assertTrue($status['output']);
        self::assertSame(0, $status['brightness']);
        self::assertSame([131, 255, 0], $status['rgb']);
        self::assertSame(5, $status['white']);
    }

    public function testWrongMacPreventsRgbwSet(): void
    {
        $http = new MockHttpClient(new MockResponse('{"id":1,"result":{"mac":"000000000000"}}'));

        try {
            (new ShellyRpcClient($http))->setRgbw(new BedLeds(), true, white: 5);
            self::fail('Wrong device must not receive RGBW.Set.');
        } catch (ShellyRpcException $exception) {
            self::assertStringContainsString('identity mismatch', $exception->getMessage());
        }

        self::assertSame(1, $http->getRequestsCount());
    }

    public function testWriteTimeoutIsNotRetried(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"id":1,"result":{"mac":"9451DC0AB154"}}'),
            new MockResponse('', ['error' => 'Lost response after write']),
        ]);

        try {
            (new ShellyRpcClient($http))->setRgbw(new BedLeds(), true, white: 5);
            self::fail('Unknown write outcome should be reported.');
        } catch (ShellyWriteOutcomeUnknownException $exception) {
            self::assertStringContainsString('was not retried', $exception->getMessage());
        }

        self::assertSame(2, $http->getRequestsCount());
    }

    public function testInvalidColorIsRejectedBeforeAnyNetworkRequest(): void
    {
        $http = new MockHttpClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('RGBW color values');

        try {
            (new ShellyRpcClient($http))->setRgbw(new BedLeds(), true, 50, 0, [256, 0, 0]);
        } finally {
            self::assertSame(0, $http->getRequestsCount());
        }
    }
}
