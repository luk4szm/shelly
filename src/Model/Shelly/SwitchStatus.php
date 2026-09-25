<?php

declare(strict_types=1);

namespace App\Model\Shelly;

use App\Model\Device\ShellyRpcDeviceInterface;

final readonly class SwitchStatus
{
    public function __construct(
        public ShellyRpcDeviceInterface $device,
        public bool                     $output,
        public ?float                   $power,
        public ?float                   $voltage,
        public ?float                   $current,
        public ?string                  $source,
        public string                   $endpoint,
        public string                   $connection,
        public float                    $responseTimeMs,
        public float                    $totalTimeMs,
    ) {}

    /** @return array<string, bool|float|int|string|null> */
    public function toArray(): array
    {
        return [
            'device'           => $this->device->getName(),
            'host'             => $this->device->getHostname(),
            'component'        => sprintf('%s:%d', $this->device->getComponentType()->value, $this->device->getChannel()),
            'endpoint'         => $this->endpoint,
            'connection'       => $this->connection,
            'response_time_ms' => $this->responseTimeMs,
            'total_time_ms'    => $this->totalTimeMs,
            'online'           => true,
            'output'           => $this->output,
            'power'            => $this->power,
            'voltage'          => $this->voltage,
            'current'          => $this->current,
            'source'           => $this->source,
        ];
    }
}
