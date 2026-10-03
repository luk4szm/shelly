<?php

declare(strict_types=1);

namespace App\Model\Scene;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class SceneRunResult
{
    /**
     * @param list<array{device: string, on: bool, brightness: ?int}> $completed
     * @param list<array{device: string, outcome: string, reason: string}> $failed
     */
    public function __construct(
        public int   $sceneId,
        public array $completed,
        public array $failed,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->failed === [];
    }

    public function toArray(): array
    {
        return [
            'scene_id'  => $this->sceneId,
            'completed' => $this->completed,
            'failed'    => $this->failed,
        ];
    }
}
