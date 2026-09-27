<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

use App\Exception\ShellyWriteOutcomeUnknownException;
use App\Exception\ShellyRpcException;
use App\Entity\Process\HydrationProcess;
use App\Model\Device\Valve\LeftSideValve;
use App\Model\Device\Valve\OrielValve;
use App\Model\Device\Valve\RightSideValve;
use App\Model\Device\Valve\RotatingValve;
use App\Model\Device\Valve\TerraceValve;
use App\Model\Device\Valve\ValveDevice;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyValveStatusReader;
use App\Service\Shelly\Local\ShellyValveWriter;
use App\Service\Shelly\Local\ShellyRpcClient;
use App\Service\Shelly\Switch\HydrationValveService;
use App\Repository\Process\HydrationProcessRepository;
use App\Service\Hydration\HydrationDeviceFinder;
use App\Service\Processable\StartHydrationProcess;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellyValveRpcTest extends TestCase
{
    /**
     * @dataProvider valveProvider
     * @param class-string<ValveDevice> $deviceClass
     */
    public function testEveryValveOpensAtFullBrightnessWithAutoOff(
        string $deviceClass,
        string $hostname,
        int    $channel,
    ): void
    {
        $device = new $deviceClass();
        $calls  = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls, $device): MockResponse {
            $calls[] = [$method, $url, json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR)];

            return new MockResponse(
                count($calls) === 1
                    ? json_encode(['id' => 1, 'result' => ['mac' => strtoupper($device->getDeviceId())]], JSON_THROW_ON_ERROR)
                    : '{"id":1,"result":null}'
            );
        });

        (new HydrationValveService(new ShellyValveWriter(new ShellyRpcClient($client))))->start($device, 90);

        self::assertSame('POST', $calls[0][0]);
        self::assertSame('POST', $calls[1][0]);
        self::assertSame('http://' . $hostname . '/rpc', $calls[0][1]);
        self::assertSame($calls[0][1], $calls[1][1]);
        self::assertSame('Shelly.GetDeviceInfo', $calls[0][2]['method']);
        self::assertArrayNotHasKey('params', $calls[0][2]);
        self::assertSame('Light.Set', $calls[1][2]['method']);
        self::assertSame(['id' => $channel, 'on' => true, 'brightness' => 100, 'toggle_after' => 90], $calls[1][2]['params']);
        self::assertSame(2, $client->getRequestsCount());
    }

    /** @return iterable<string, array{class-string<ValveDevice>, string, int}> */
    public function valveProvider(): iterable
    {
        yield 'left' => [LeftSideValve::class, 'shellyplusrgbwpm-30c922573230.local', 3];
        yield 'rotating' => [RotatingValve::class, 'shellyplusrgbwpm-30c922573230.local', 2];
        yield 'terrace' => [TerraceValve::class, 'shellyplusrgbwpm-30c922573230.local', 1];
        yield 'oriel' => [OrielValve::class, 'shellyplusrgbwpm-9451dc0ac424.local', 2];
        yield 'right' => [RightSideValve::class, 'shellyplusrgbwpm-9451dc0ac424.local', 3];
    }

    public function testStopDoesNotSpecifyBrightnessOrTimer(): void
    {
        $calls  = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $calls[] = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);

            return new MockResponse(
                count($calls) === 1
                    ? '{"id":1,"result":{"mac":"30C922573230"}}'
                    : '{"id":1,"result":null}'
            );
        });

        (new HydrationValveService(new ShellyValveWriter(new ShellyRpcClient($client))))->stop(new LeftSideValve());

        self::assertSame(['id' => 3, 'on' => false], $calls[1]['params']);
    }

    public function testInvalidDurationNeverSendsARequest(): void
    {
        $client = new MockHttpClient();

        try {
            (new ShellyRpcClient($client))->setValve(new LeftSideValve(), true, 0);
            self::fail('Zero duration should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('positive toggle_after', $exception->getMessage());
        }

        self::assertSame(0, $client->getRequestsCount());
    }

    public function testStatusReadIncludesBrightnessAndTimer(): void
    {
        $client = new MockHttpClient(new MockResponse('{"id":1,"result":{"id":3,"output":true,"brightness":100,"timer_started_at":123,"timer_duration":90,"source":"HTTP_in"}}'));
        $reader = new ShellyValveStatusReader(new ShellyDeviceRegistry([new LeftSideValve()]), new ShellyRpcClient($client));

        $status = $reader->read(LeftSideValve::NAME);

        self::assertSame('light:3', $status['component']);
        self::assertTrue($status['output']);
        self::assertSame(100, $status['brightness']);
        self::assertSame(90, $status['timer_duration']);
    }

    public function testWriteTransportFailureIsNotRetriedAtAnotherAddress(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"id":1,"result":{"mac":"30C922573230"}}'),
            new MockResponse('', ['error' => 'Lost response after write']),
        ]);

        try {
            (new ShellyRpcClient($client))->setValve(new LeftSideValve(), true, 90);
            self::fail('Unknown write outcome should be reported.');
        } catch (ShellyWriteOutcomeUnknownException $exception) {
            self::assertStringContainsString('was not retried', $exception->getMessage());
        }

        self::assertSame(2, $client->getRequestsCount());
    }

    public function testFallbackIpIsSelectedByReadBeforeSingleWrite(): void
    {
        $urls = [];
        $client = new MockHttpClient(function (string $method, string $url) use (&$urls): MockResponse {
            $urls[] = $url;

            return match (count($urls)) {
                1 => new MockResponse('', ['error' => 'mDNS unavailable']),
                2 => new MockResponse('{"id":1,"result":{"mac":"30C922573230"}}'),
                default => new MockResponse('{"id":1,"result":null}'),
            };
        });

        (new ShellyRpcClient($client))->setValve(new LeftSideValve(), true, 90);

        self::assertSame([
            'http://shellyplusrgbwpm-30c922573230.local/rpc',
            'http://192.168.1.99/rpc',
            'http://192.168.1.99/rpc',
        ], $urls);
    }

    public function testFallbackIpPointingAtAnotherDeviceNeverReceivesSet(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['error' => 'mDNS unavailable']),
            new MockResponse('{"id":1,"result":{"mac":"000000000000"}}'),
        ]);

        try {
            (new ShellyRpcClient($client))->setValve(new LeftSideValve(), true, 90);
            self::fail('Wrong device must not receive Light.Set.');
        } catch (ShellyRpcException $exception) {
            self::assertStringContainsString('identity mismatch', $exception->getMessage());
        }

        self::assertSame(2, $client->getRequestsCount());
    }

    public function testUnknownScheduledWriteOutcomeIsLoggedAndNotRetried(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"id":1,"result":{"mac":"30C922573230"}}'),
            new MockResponse('', ['error' => 'Lost response after write']),
        ]);
        $valveService = new HydrationValveService(new ShellyValveWriter(new ShellyRpcClient($client)));
        $repository   = $this->createMock(HydrationProcessRepository::class);
        $repository->expects(self::once())->method('save');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with(self::stringContains('was not retried'));
        $process  = (new HydrationProcess())->setValve(LeftSideValve::NAME)->setDuration(90);
        $consumer = new StartHydrationProcess(
            [],
            $valveService,
            new HydrationDeviceFinder([new LeftSideValve()]),
            $repository,
            $logger,
        );

        $consumer->process($process);

        self::assertNotNull($process->getExecutedAt());
        self::assertSame(2, $client->getRequestsCount());
    }
}
