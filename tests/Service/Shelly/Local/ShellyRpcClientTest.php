<?php

declare(strict_types=1);

namespace App\Tests\Service\Shelly\Local;

use App\Model\Device\Relay\Garland;
use App\Exception\ShellyDeviceUnavailableException;
use App\Service\Shelly\Local\ShellyRpcClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellyRpcClientTest extends TestCase
{
    public function testItReadsAndUnwrapsAnRpcResponse(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('http://shellyplus2pm-345f45193b80.local/rpc', $url);
            self::assertJsonStringEqualsJsonString(
                '{"id":1,"method":"Switch.GetStatus","params":{"id":1}}',
                $options['body'],
            );

            return new MockResponse(json_encode([
                'id'     => 1,
                'src'    => 'shellyplus2pm-345f45193b80',
                'result' => [
                    'id'     => 1,
                    'output' => true,
                    'apower' => 24.5,
                ],
            ], JSON_THROW_ON_ERROR));
        });

        $device = new Garland();
        $result = (new ShellyRpcClient($httpClient))->read($device, 'Switch.GetStatus', ['id' => 1]);

        self::assertTrue($result->data['output']);
        self::assertSame(24.5, $result->data['apower']);
        self::assertSame('ShellyPlus2PM-345F45193B80.local', $result->endpoint);
        self::assertSame('mdns', $result->connection);
    }

    public function testItRejectsWriteMethods(): void
    {
        $httpClient = new MockHttpClient();
        $device     = new Garland();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not allowed by the read-only client');

        (new ShellyRpcClient($httpClient))->read($device, 'Switch.Set', ['id' => 1, 'on' => true]);
    }

    public function testItUsesTheFallbackIpAfterAMdnsTransportFailure(): void
    {
        $urls = [];

        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$urls): MockResponse {
            $urls[] = $url;

            return count($urls) === 1
                ? new MockResponse('', ['error' => 'Temporary network failure'])
                : new MockResponse('{"id":1,"result":{"id":1,"output":false}}');
        });

        $device = new Garland();
        $result = (new ShellyRpcClient($httpClient))->read($device, 'Switch.GetStatus', ['id' => 1]);

        self::assertFalse($result->data['output']);
        self::assertSame('192.168.1.118', $result->endpoint);
        self::assertSame('fallback_ip', $result->connection);
        self::assertSame([
            'http://shellyplus2pm-345f45193b80.local/rpc',
            'http://192.168.1.118/rpc',
        ], $urls);
        self::assertSame(2, $httpClient->getRequestsCount());
    }

    public function testItReportsTheHostnameAndTransportErrorWhenADeviceIsUnavailable(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('', ['error' => 'Could not resolve host']),
            new MockResponse('', ['error' => 'Could not resolve host']),
        ]);

        $this->expectException(ShellyDeviceUnavailableException::class);
        $this->expectExceptionMessage(
            'Shelly device "girlanda" is unavailable via all configured endpoints',
        );

        (new ShellyRpcClient($httpClient))->read(new Garland(), 'Switch.GetStatus', ['id' => 1]);
    }
}
