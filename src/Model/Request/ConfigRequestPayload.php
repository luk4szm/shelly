<?php

namespace App\Model\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ConfigRequestPayload
{
    public function __construct(
        #[Assert\Choice(['occupancy_mode', 'daylight_mode', 'auto_light_inside', 'auto_light_outside'])]
        public ?string $name,
        public mixed   $value,
    ) {
    }
}
