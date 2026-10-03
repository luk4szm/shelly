<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Scene;

use App\Model\Device\Light\KitchenLedsBottom;
use App\Model\Device\Light\KitchenLedsTop;
use App\Model\Device\Light\TvLedsBoard;
use App\Model\Device\Light\TvLedsCabinet;
use App\Model\Device\Light\TvLedsMonitor;
use App\Model\Device\Relay\DrivewayLights;
use App\Model\Device\Relay\GardenHalogen;
use App\Model\Device\Relay\Garland;
use App\Model\Scene\TurnOffInsideLightsScene;
use App\Model\Scene\TurnOffKitchenLightsScene;
use App\Model\Scene\TurnOffLightsScene;
use App\Model\Scene\TurnOffOutsideLightsScene;
use App\Model\Scene\TurnOffTvLedsScene;
use App\Model\Scene\TurnOnInsideLightsScene;
use App\Model\Scene\TurnOnKitchenLightsScene;
use App\Model\Scene\TurnOnOutsideLightsScene;
use App\Model\Scene\TurnOnTvLedsScene;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyLedWriter;
use App\Service\Shelly\Local\ShellyRpcClient;
use App\Service\Shelly\Local\ShellySwitchWriter;
use App\Service\Shelly\Scene\SceneRegistry;
use App\Service\Shelly\Scene\ShellySceneService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellySceneServiceTest extends TestCase
{
    /** @dataProvider scenes */
    public function testSceneUsesExactlyItsLocalRpcActions(int $sceneId, array $expected): void
    {
        $calls = [];
        $service = $this->service(new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            self::assertSame('POST', $method);
            self::assertStringEndsWith('/rpc', $url);
            $request = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);
            $calls[] = $request;

            if ($request['method'] === 'Shelly.GetDeviceInfo') {
                $mac = match (true) {
                    str_contains($url, '345f45193b80') => '345F45193B80',
                    str_contains($url, 'b0b21c103c08') => 'B0B21C103C08',
                    str_contains($url, '2cbcbbc16fa4') => '2CBCBBC16FA4',
                    str_contains($url, 'ecc9ff4dc3f4') => 'ECC9FF4DC3F4',
                    default => throw new \LogicException('Unexpected local endpoint: ' . $url),
                };

                return new MockResponse(json_encode(['id' => 1, 'result' => ['mac' => $mac]], JSON_THROW_ON_ERROR));
            }

            return new MockResponse('{"id":1,"result":{}}');
        }));

        $result = $service->trigger($sceneId);

        self::assertTrue($result->isSuccessful(), json_encode($result->failed));
        self::assertCount(count($expected), $result->completed);
        $writes = array_values(array_filter($calls, fn (array $call): bool => $call['method'] !== 'Shelly.GetDeviceInfo'));
        self::assertSame($expected, array_map(fn (array $write): array => [$write['method'], $write['params']], $writes));
        self::assertCount(2 * count($expected), $calls);
    }

    public function scenes(): iterable
    {
        $light = fn (int $channel, bool $on, ?int $brightness = null): array => [
            'Light.Set', array_filter(['id' => $channel, 'on' => $on, 'brightness' => $brightness], fn ($value) => $value !== null),
        ];
        $switch = fn (int $channel, bool $on): array => ['Switch.Set', ['id' => $channel, 'on' => $on]];

        yield 'kitchen on' => [TurnOnKitchenLightsScene::ID, [$light(0, true, 65), $light(1, true, 10)]];
        yield 'kitchen off' => [TurnOffKitchenLightsScene::ID, [$light(0, false), $light(1, false)]];
        yield 'TV on' => [TurnOnTvLedsScene::ID, [$light(2, true, 15), $light(0, true, 10), $light(1, true, 5)]];
        yield 'TV off' => [TurnOffTvLedsScene::ID, [$light(2, false), $light(0, false), $light(1, false)]];
        yield 'all off' => [TurnOffLightsScene::ID, [
            $light(0, false), $light(1, false), $light(2, false), $light(0, false), $light(1, false),
            $switch(1, false), $switch(0, false), $switch(0, false),
        ]];
        yield 'outside off' => [TurnOffOutsideLightsScene::ID, [$switch(1, false), $switch(0, false)]];
        yield 'outside on' => [TurnOnOutsideLightsScene::ID, [$switch(1, true), $switch(0, true)]];
        yield 'inside on' => [TurnOnInsideLightsScene::ID, [
            $light(1, true, 10), $light(0, true, 65), $light(0, true, 10), $light(1, true, 5), $light(2, true, 15),
        ]];
        yield 'inside off' => [TurnOffInsideLightsScene::ID, [
            $light(0, false), $light(1, false), $light(2, false), $light(0, false), $light(1, false),
        ]];
    }

    public function testFailedWriteIsNotRetriedAndLaterActionsContinue(): void
    {
        $calls = [];
        $service = $this->service(new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $request = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);
            $calls[] = $request['method'];

            if ($request['method'] === 'Shelly.GetDeviceInfo') {
                $mac = str_contains($url, 'b0b21c103c08') ? 'B0B21C103C08' : '345F45193B80';

                return new MockResponse(json_encode(['id' => 1, 'result' => ['mac' => $mac]], JSON_THROW_ON_ERROR));
            }

            return count($calls) === 2
                ? new MockResponse('', ['error' => 'Lost response after write'])
                : new MockResponse('{"id":1,"result":{}}');
        }));

        $result = $service->trigger(TurnOnOutsideLightsScene::ID);

        self::assertFalse($result->isSuccessful());
        self::assertSame('unknown', $result->failed[0]['outcome']);
        self::assertSame(Garland::NAME, $result->failed[0]['device']);
        self::assertSame(DrivewayLights::NAME, $result->completed[0]['device']);
        self::assertSame(['Shelly.GetDeviceInfo', 'Switch.Set', 'Shelly.GetDeviceInfo', 'Switch.Set'], $calls);
    }

    public function testUnknownSceneDoesNotFallBackToCloud(): void
    {
        $service = $this->service(new MockHttpClient());
        $this->expectException(\InvalidArgumentException::class);
        $service->trigger('9999999999999');
    }

    public function testAllOffContainsOnlyEightAgreedOutputs(): void
    {
        $actions = (new TurnOffLightsScene())->getActions();

        self::assertSame([
            KitchenLedsTop::NAME,
            KitchenLedsBottom::NAME,
            TvLedsMonitor::NAME,
            TvLedsBoard::NAME,
            TvLedsCabinet::NAME,
            Garland::NAME,
            DrivewayLights::NAME,
            GardenHalogen::NAME,
        ], array_map(fn ($action) => $action->deviceName, $actions));
        self::assertSame(0, (new GardenHalogen())->getChannel());
        self::assertSame((new Garland())->getDeviceId(), (new GardenHalogen())->getDeviceId());
    }

    public function testWrongDeviceIdentityPreventsRelayWriteAndContinues(): void
    {
        $calls = [];
        $service = $this->service(new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $request = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);
            $calls[] = $request['method'];

            if ($request['method'] === 'Shelly.GetDeviceInfo') {
                $mac = str_contains($url, 'b0b21c103c08') ? 'B0B21C103C08' : '000000000000';

                return new MockResponse(json_encode(['id' => 1, 'result' => ['mac' => $mac]], JSON_THROW_ON_ERROR));
            }

            return new MockResponse('{"id":1,"result":{}}');
        }));

        $result = $service->trigger(TurnOnOutsideLightsScene::ID);

        self::assertSame(Garland::NAME, $result->failed[0]['device']);
        self::assertSame(DrivewayLights::NAME, $result->completed[0]['device']);
        self::assertSame(['Shelly.GetDeviceInfo', 'Shelly.GetDeviceInfo', 'Switch.Set'], $calls);
    }

    private function service(MockHttpClient $httpClient): ShellySceneService
    {
        $devices = new ShellyDeviceRegistry([
            new KitchenLedsTop(), new KitchenLedsBottom(), new TvLedsMonitor(), new TvLedsBoard(), new TvLedsCabinet(),
            new Garland(), new DrivewayLights(), new GardenHalogen(),
        ]);
        $rpc = new ShellyRpcClient($httpClient);
        $scenes = new SceneRegistry([
            new TurnOnKitchenLightsScene(), new TurnOffKitchenLightsScene(), new TurnOnTvLedsScene(), new TurnOffTvLedsScene(),
            new TurnOffLightsScene(), new TurnOffOutsideLightsScene(), new TurnOnOutsideLightsScene(),
            new TurnOnInsideLightsScene(), new TurnOffInsideLightsScene(),
        ]);

        return new ShellySceneService($scenes, $devices, new ShellyLedWriter($rpc), new ShellySwitchWriter($devices, $rpc));
    }
}
