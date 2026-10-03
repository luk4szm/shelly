<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

use App\Exception\ShellyDeviceUnavailableException;
use App\Exception\ShellyRpcException;
use App\Exception\ShellyWriteOutcomeUnknownException;
use App\Model\Device\Cover\RollerCover;
use App\Model\Device\Relay\Garland;
use App\Service\Shelly\Local\ShellyCoverStatusReader;
use App\Service\Shelly\Local\ShellyCoverWriter;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyRpcClient;
use App\Service\Shelly\Cover\ShellyCoverService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellyCoverRpcTest extends TestCase
{
    public function testStatusReportsActualPositionRatherThanInferringItFromLastDirection(): void
    {
        $http = new MockHttpClient(new MockResponse(
            '{"id":1,"result":{"id":0,"state":"stopped","last_direction":"open","current_pos":42,"source":"button"}}',
        ));
        $reader = new ShellyCoverStatusReader(
            new ShellyDeviceRegistry([new RollerCover()]),
            new ShellyRpcClient($http),
        );

        $status = $reader->read(RollerCover::NAME);

        self::assertSame('cover:0', $status['component']);
        self::assertSame('stopped', $status['state']);
        self::assertSame('open', $status['last_direction']);
        self::assertSame(42, $status['current_pos']);
        self::assertSame('ShellyPlus2PM-2CBCBB2DC408.local', $status['endpoint']);
        self::assertSame(1, $http->getRequestsCount());
    }

    public function testMissingStateIsNotMistakenForClosedCover(): void
    {
        $http = new MockHttpClient(new MockResponse('{"id":1,"result":{"last_direction":"close"}}'));
        $reader = new ShellyCoverStatusReader(
            new ShellyDeviceRegistry([new RollerCover()]),
            new ShellyRpcClient($http),
        );

        $this->expectException(ShellyRpcException::class);
        $this->expectExceptionMessage('no valid state');

        $reader->read(RollerCover::NAME);
    }

    /** @dataProvider actionProvider */
    public function testEachActionSendsOneVerifiedRpcCommand(string $action, string $method): void
    {
        $calls = [];
        $http = new MockHttpClient(function (string $httpMethod, string $url, array $options) use (&$calls): MockResponse {
            $calls[] = [$httpMethod, $url, json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR)];

            return new MockResponse(count($calls) === 1
                ? '{"id":1,"result":{"mac":"2CBCBB2DC408"}}'
                : '{"id":1,"result":null}');
        });
        $writer = new ShellyCoverWriter(
            new ShellyDeviceRegistry([new RollerCover()]),
            new ShellyRpcClient($http),
        );

        $result = $writer->set(RollerCover::NAME, $action);

        self::assertSame([], $result->data);
        self::assertSame('mdns', $result->connection);
        self::assertSame(2, $http->getRequestsCount());
        self::assertSame('POST', $calls[0][0]);
        self::assertSame('Shelly.GetDeviceInfo', $calls[0][2]['method']);
        self::assertSame('http://shellyplus2pm-2cbcbb2dc408.local/rpc', $calls[1][1]);
        self::assertSame($method, $calls[1][2]['method']);
        self::assertSame(['id' => 0], $calls[1][2]['params']);
    }

    /** @return iterable<string, array{string, string}> */
    public function actionProvider(): iterable
    {
        yield 'open' => ['open', 'Cover.Open'];
        yield 'close' => ['close', 'Cover.Close'];
        yield 'stop' => ['stop', 'Cover.Stop'];
    }

    public function testFallbackIpIsVerifiedBeforeWrite(): void
    {
        $urls = [];
        $http = new MockHttpClient(function (string $method, string $url) use (&$urls): MockResponse {
            $urls[] = $url;

            return match (count($urls)) {
                1 => new MockResponse('', ['error' => 'mDNS unavailable']),
                2 => new MockResponse('{"id":1,"result":{"mac":"2CBCBB2DC408"}}'),
                default => new MockResponse('{"id":1,"result":null}'),
            };
        });

        (new ShellyRpcClient($http))->setCover(new RollerCover(), 'open');

        self::assertSame([
            'http://shellyplus2pm-2cbcbb2dc408.local/rpc',
            'http://192.168.1.51/rpc',
            'http://192.168.1.51/rpc',
        ], $urls);
    }

    public function testWrongMacPreventsMovement(): void
    {
        $http = new MockHttpClient([
            new MockResponse('', ['error' => 'mDNS unavailable']),
            new MockResponse('{"id":1,"result":{"mac":"000000000000"}}'),
        ]);

        try {
            (new ShellyRpcClient($http))->setCover(new RollerCover(), 'open');
            self::fail('Wrong device must not receive Cover.Open.');
        } catch (ShellyRpcException $exception) {
            self::assertStringContainsString('identity mismatch', $exception->getMessage());
        }

        self::assertSame(2, $http->getRequestsCount());
    }

    public function testUnknownWriteOutcomeIsNotRetried(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"id":1,"result":{"mac":"2CBCBB2DC408"}}'),
            new MockResponse('', ['error' => 'Lost response after write']),
        ]);

        try {
            (new ShellyRpcClient($http))->setCover(new RollerCover(), 'open');
            self::fail('Unknown write outcome should be reported.');
        } catch (ShellyWriteOutcomeUnknownException $exception) {
            self::assertStringContainsString('was not retried', $exception->getMessage());
        }

        self::assertSame(2, $http->getRequestsCount());
    }

    public function testUnavailableCoverDoesNotFallBackToCloud(): void
    {
        $http = new MockHttpClient([
            new MockResponse('', ['error' => 'mDNS unavailable']),
            new MockResponse('', ['error' => 'IP unavailable']),
        ]);
        $reader = new ShellyCoverStatusReader(
            new ShellyDeviceRegistry([new RollerCover()]),
            new ShellyRpcClient($http),
        );

        $this->expectException(ShellyDeviceUnavailableException::class);
        $reader->read(RollerCover::NAME);
    }

    public function testOtherDeviceCannotBeControlledAsCover(): void
    {
        $http = new MockHttpClient();
        $writer = new ShellyCoverWriter(
            new ShellyDeviceRegistry([new Garland()]),
            new ShellyRpcClient($http),
        );

        $this->expectException(ShellyRpcException::class);

        try {
            $writer->set(Garland::NAME, 'open');
        } finally {
            self::assertSame(0, $http->getRequestsCount());
        }
    }

    public function testApplicationOpenUsesOneCoverCommandWithoutWaitingForASecond(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"id":1,"result":{"mac":"2CBCBB2DC408"}}'),
            new MockResponse('{"id":1,"result":null}'),
        ]);
        $registry = new ShellyDeviceRegistry([new RollerCover()]);
        $rpc = new ShellyRpcClient($http);
        $service = new ShellyCoverService(
            new ShellyCoverWriter($registry, $rpc),
            new ShellyCoverStatusReader($registry, $rpc),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Security::class),
        );

        self::assertSame([], $service->open());
        self::assertSame(2, $http->getRequestsCount());
    }
}
