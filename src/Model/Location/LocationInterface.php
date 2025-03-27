<?php

namespace App\Model\Location;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.locations')]
interface LocationInterface
{
    public function getName(): string;
}
