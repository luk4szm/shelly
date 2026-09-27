<?php

declare(strict_types=1);

namespace App\Service\Shelly\Local;

use App\Enum\ShellyComponentType;
use App\Exception\ShellyDeviceUnavailableException;
use App\Exception\ShellyRpcException;
use App\Exception\ShellyWriteOutcomeUnknownException;
use App\Model\Device\ShellyRpcDeviceInterface;
use App\Model\Device\Valve\ValveDevice;
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

    /** Shelly exposes these valve outputs as Light components in its RPC protocol. */
    public function setValve(ValveDevice $device, bool $on, int $toggleAfter = 0): RpcResult
    {
        if ($device->getComponentType() !== ShellyComponentType::Light) {
            throw new \InvalidArgumentException(sprintf('Shelly device "%s" is not configured as a light.', $device->getName()));
        }

        if ($on && ($toggleAfter < 1 || $toggleAfter > 604800)) {
            throw new \InvalidArgumentException('Valve opening requires a positive toggle_after of at most 604800 seconds.');
        }

        $params = ['id' => $device->getChannel(), 'on' => $on];

        if ($on) {
            $params['brightness']   = 100;
            $params['toggle_after'] = $toggleAfter;
        }

        // Verify the physical device before writing, especially when a DHCP fallback IP is used.
        // The write itself is sent once: retrying after a timeout could extend watering.
        $endpoint = $this->read($device, 'Shelly.GetDeviceInfo');

        if (strtolower((string) ($endpoint->data['mac'] ?? '')) !== strtolower($device->getDeviceId())) {
            throw new ShellyRpcException(sprintf(
                'Shelly device identity mismatch for "%s" at %s; Light.Set was not sent.',
                $device->getName(),
                $endpoint->endpoint,
            ));
        }
        $startedAt = hrtime(true);

        try {
            $data = $this->send($endpoint->endpoint, 'Light.Set', $params);
        } catch (TransportExceptionInterface|ShellyRpcException $exception) {
            throw new ShellyWriteOutcomeUnknownException(sprintf(
                'Light.Set outcome for Shelly device "%s" is unknown; the command was not retried.',
                $device->getName(),
            ), previous: $exception);
        }

        return new RpcResult(
            $data,
            $endpoint->endpoint,
            $endpoint->connection,
            self::elapsedMilliseconds($startedAt),
            $endpoint->totalTimeMs + self::elapsedMilliseconds($startedAt),
        );
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
        $operationStartedAt = hrtime(true);

        foreach ($endpoints as $endpoint) {
            $attemptStartedAt = hrtime(true);

            try {
                $data = $this->send($endpoint['address'], $method, $params);

                return new RpcResult(
                    $data,
                    $endpoint['address'],
                    $endpoint['connection'],
                    self::elapsedMilliseconds($attemptStartedAt),
                    self::elapsedMilliseconds($operationStartedAt),
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

    private static function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
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

        if (!array_key_exists('result', $data)) {
            throw new ShellyRpcException(sprintf('Shelly RPC "%s" returned an invalid response.', $method));
        }

        $result = $data['result'];

        if ($method === 'Light.Set' && $result === null) {
            return [];
        }

        if (!is_array($result)) {
            throw new ShellyRpcException(sprintf('Shelly RPC "%s" returned an invalid response.', $method));
        }

        if ($method === 'Light.Set' && isset($result['results'])) {
            foreach ($result['results'] as $channelResult) {
                if ($channelResult !== null) {
                    throw new ShellyRpcException(sprintf('Shelly RPC "%s" did not apply to all channels.', $method));
                }
            }
        }

        return $result;
    }
}
