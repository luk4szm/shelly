<?php

declare(strict_types=1);

namespace App\Model\Shelly;

final readonly class RpcResult
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public array  $data,
        public string $endpoint,
        public string $connection,
    ) {}
}
