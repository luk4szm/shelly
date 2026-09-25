<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Enum\ShellyComponentType;
use App\Exception\ShellyDeviceUnavailableException;
use App\Exception\ShellyRpcException;
use App\Model\Device\ShellyRpcDeviceInterface;
use App\Model\Shelly\RpcResult;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ShellyRpcClient
{
    private const CONNECT_TIMEOUT_SECONDS = 1.0;
    private const MAX_DURATION_SECONDS    = 2.0;

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function read(ShellyRpcDeviceInterface $device, string $method, array $params = []): RpcResult
    {
        if (preg_match('/\.(?:Get|List)/', $method) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'RPC method "%s" is not allowed by the read-only client.',
                $method,
            ));
        }

        return $this->request($device, $method, $params);
    }

    public function setSwitch(ShellyRpcDeviceInterface $device, bool $on): RpcResult
    {
        if ($device->getComponentType() !== ShellyComponentType::Switch) {
            throw new \InvalidArgumentException(sprintf(
                'Shelly device "%s" is not configured as a switch.',
                $device->getName(),
            ));
        }

        return $this->request($device, 'Switch.Set', [
            'id' => $device->getChannel(),
            'on' => $on,
        ]);
    }

    private function request(ShellyRpcDeviceInterface $device, string $method, array $params): RpcResult
    {
        $endpoints = [
            ['address' => $device->getHostname(), 'connection' => 'mdns'],
        ];

        if (null !== $fallbackIp = $device->getFallbackIp()) {
            if (filter_var($fallbackIp, FILTER_VALIDATE_IP) === false) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid fallback IP "%s" configured for Shelly device "%s".',
                    $fallbackIp,
                    $device->getName(),
                ));
            }

            $endpoints[] = ['address' => $fallbackIp, 'connection' => 'fallback_ip'];
        }

        $failures = [];

        foreach ($endpoints as $endpoint) {
            try {
                return new RpcResult(
                    $this->send($endpoint['address'], $method, $params),
                    $endpoint['address'],
                    $endpoint['connection'],
                );
            } catch (TransportExceptionInterface $exception) {
                $failures[] = sprintf('%s: %s', $endpoint['address'], $exception->getMessage());
                $lastException = $exception;
            }
        }

        throw new ShellyDeviceUnavailableException(
            sprintf(
                'Shelly device "%s" is unavailable via all configured endpoints (%s).',
                $device->getName(),
                implode('; ', $failures),
            ),
            previous: $lastException,
        );
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $params
     * @return array<string, mixed>
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function send(string $endpoint, string $method, array $params): array
    {
        $payload = [
            'id' => 1,
            'method' => $method,
        ];

        if ($params !== []) {
            $payload['params'] = $params;
        }

        $response = $this->httpClient->request(
            'POST',
            sprintf('http://%s/rpc', $endpoint),
            [
                'json' => $payload,
                'timeout' => self::CONNECT_TIMEOUT_SECONDS,
                'max_duration' => self::MAX_DURATION_SECONDS,
            ],
        );

        $statusCode = $response->getStatusCode();

        try {
            $data = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ShellyRpcException(
                sprintf('Shelly RPC "%s" returned invalid JSON.', $method),
                previous: $exception,
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ShellyRpcException(sprintf(
                'Shelly RPC "%s" failed with HTTP status %d.',
                $method,
                $statusCode,
            ));
        }

        if (!is_array($data)) {
            throw new ShellyRpcException(sprintf('Shelly RPC "%s" returned an invalid response.', $method));
        }

        if (isset($data['error'])) {
            throw new ShellyRpcException(sprintf(
                'Shelly RPC "%s" failed: %s',
                $method,
                is_array($data['error']) ? ($data['error']['message'] ?? 'unknown RPC error') : (string) $data['error'],
            ));
        }

        $result = $data['result'] ?? null;

        if (!is_array($result)) {
            throw new ShellyRpcException(sprintf('Shelly RPC "%s" returned an invalid response.', $method));
        }

        return $result;
    }
}
