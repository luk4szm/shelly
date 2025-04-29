<?php

namespace App\Service\Curl;

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class Curl
{
    protected function request(string $method, string $url, array $body = [], array $json = []): ?array
    {
        $client      = new CurlHttpClient();
        $curlRequest = $client->request($method, $url, self::prepareOptions($body, $json));

        try {
            return $curlRequest->toArray();
        } catch (
            ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface |ServerExceptionInterface | TransportExceptionInterface $e
        ) {
            return ['error' => $e->getMessage()];
        }
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
