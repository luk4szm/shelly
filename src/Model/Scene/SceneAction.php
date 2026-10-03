<?php

declare(strict_types=1);

namespace App\Model\Scene;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class SceneAction
{
    private function __construct(
        public string $deviceName,
        public bool   $on,
        public ?int   $brightness = null,
    ) {}

    public static function on(string $deviceName, ?int $brightness = null): self
    {
        return new self($deviceName, true, $brightness);
    }

    public static function off(string $deviceName): self
    {
        return new self($deviceName, false);
    }
}
