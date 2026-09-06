<?php

namespace App\Tests\Service\Curl;

use App\Exception\ShellyRateLimitException;
use App\Service\Curl\Curl;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ShellyCloudCurlRequestTest extends TestCase
{
    /** @dataProvider responses */
    public function testRetriesOnlyRateLimits(array $codes, array $delays, bool $fails, bool $read): void
    {
        $requests = [];
        $client = new MockHttpClient(function ($method, $url, $options) use (&$requests, $codes) {
            $index = count($requests);
            $requests[] = [$method, $url, $options['body']];
            self::assertArrayHasKey($index, $codes, 'Unexpected extra request');

            return new MockResponse('{"ok":true}', ['http_code' => $codes[$index]]);
        });
        $shelly = new class($client) extends ShellyCloudCurlRequest {
            public array $delays = [];
            protected function waitBeforeRetry(int $seconds): void { $this->delays[] = $seconds; }
        };
        $oldKey = $_ENV['SHELLY_AUTH_KEY'] ?? null;
        $_ENV['SHELLY_AUTH_KEY'] = 'test-secret';
        try {
            try {
                $result = $read ? $shelly->getStatus('device-123') : $shelly->switch('device-123', 2, 'on');
                self::assertFalse($fails, 'Expected rate limit exception');
                if (end($codes) === 200) {
                    self::assertSame(['ok' => true], $result);
                } else {
                    self::assertArrayHasKey('error', $result);
                }
            } catch (ShellyRateLimitException $e) {
                self::assertTrue($fails);
                self::assertSame(429, $e->getCode());
                self::assertStringNotContainsString('test-secret', $e->getMessage());
                self::assertNull($e->getPrevious());
            }
            self::assertCount(count($codes), $requests);
            self::assertSame($delays, $shelly->delays);
            foreach ($requests as $request) {
                self::assertSame($requests[0], $request);
            }
        } finally {
            if ($oldKey === null) {
                unset($_ENV['SHELLY_AUTH_KEY']);
            } else {
                $_ENV['SHELLY_AUTH_KEY'] = $oldKey;
            }
        }
    }

    public function responses(): iterable
    {
        foreach ([false, true] as $read) {
            yield [[200], [], false, $read];
            yield [[429, 200], [1], false, $read];
            yield [[429, 429, 200], [1, 2], false, $read];
            yield [[429, 429, 429, 200], [1, 2, 3], false, $read];
            yield [[429, 429, 429, 429], [1, 2, 3], true, $read];
            yield [[401], [], false, $read];
            yield [[500], [], false, $read];
            yield [[429, 500], [1], false, $read];
        }
    }

    public function testOtherIntegrationsDoNotRetry(): void
    {
        $client = new MockHttpClient([new MockResponse('{}', ['http_code' => 429])]);
        $curl = new class($client) extends Curl {
            public function send(): array { return $this->request('GET', 'https://example.test'); }
            protected function waitBeforeRetry(int $seconds): void { throw new \LogicException('Unexpected retry'); }
        };
        self::assertArrayHasKey('error', $curl->send());
        self::assertSame(1, $client->getRequestsCount());
    }
}
