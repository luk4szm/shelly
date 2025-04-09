<?php

namespace App\Service\Curl;

use Symfony\Component\HttpClient\CurlHttpClient;

abstract class Curl
{
    protected function request(string $method, string $url, array $body = []): array
    {
        $client = new CurlHttpClient();

        $curlRequest = $client->request($method, $url, [
            'body' => $body,
        ]);

        return $curlRequest->toArray();
    }
}
