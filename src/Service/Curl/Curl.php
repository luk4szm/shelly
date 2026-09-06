<?php

namespace App\Service\Curl;

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class Curl
{
    public function __construct(
        private readonly HttpClientInterface $client = new CurlHttpClient()
    ) {}

    protected function request(string $method, string $url, array $body = [], array $json = []): ?array
    {
        $delays  = $this->retryDelays();
        $options = self::prepareOptions($body, $json);

        for ($retry = 0; ; ++$retry) {
            try {
                $response = $this->client->request($method, $url, $options);

                if ($response->getStatusCode() === 429) {
                    if (isset($delays[$retry])) {
                        $response->cancel();
                        $this->waitBeforeRetry($delays[$retry]);
                        continue;
                    }

                    $this->onRateLimitExhausted();
                }

                return $response->toArray();
            } catch (
                ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e
            ) {
                return ['error' => $e->getMessage()];
            }
        }
    }

    /** @return list<int> Delays in seconds; no retries unless enabled by the integration. */
    protected function retryDelays(): array
    {
        return [];
    }

    protected function waitBeforeRetry(int $seconds): void
    {
        sleep($seconds);
    }

    protected function onRateLimitExhausted(): void
    {
    }

    private static function prepareOptions(array $body = [], array $json = []): array
    {
        if (!empty($json)) {
            return [
                'json' => $json,
            ];
        }

        return [
            'body' => $body,
        ];
    }
}
