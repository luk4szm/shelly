<?php

namespace App\Service\Curl;

use Symfony\Component\HttpClient\CurlHttpClient;

abstract class Curl
{
    protected function request(string $method, string $url, array $headers = [], array $body = []): ?array
    {
        $client = new CurlHttpClient();

        $curlRequest = $client->request($method, $url, [
            'headers' => $headers,
            'body'    => $body,
        ]);

        return $curlRequest->toArray();
    }
}
