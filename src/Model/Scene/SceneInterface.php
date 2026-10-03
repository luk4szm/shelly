<?php

declare(strict_types=1);

namespace App\Model\Scene;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.scene')]
interface SceneInterface
{
    public function getId(): int;

    public function getName(): string;

    /** @return list<SceneAction> */
    public function getActions(): array;
}
