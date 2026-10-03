<?php

declare(strict_types=1);

namespace App\Service\Shelly\Scene;

use App\Model\Scene\SceneInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class SceneRegistry
{
    /** @var array<int, SceneInterface> */
    private array $scenes = [];

    /** @param iterable<SceneInterface> $scenes */
    public function __construct(#[AutowireIterator('app.shelly.scene')] iterable $scenes)
    {
        foreach ($scenes as $scene) {
            if (isset($this->scenes[$scene->getId()])) {
                throw new \LogicException(sprintf('Duplicate Shelly scene ID "%d".', $scene->getId()));
            }

            $this->scenes[$scene->getId()] = $scene;
        }
    }

    public function get(string|int $id): SceneInterface
    {
        if (!ctype_digit((string) $id)) {
            throw new \InvalidArgumentException(sprintf('Invalid scene ID "%s".', $id));
        }

        return $this->scenes[(int) $id]
            ?? throw new \InvalidArgumentException(sprintf('Unknown local scene "%s".', $id));
    }
}
