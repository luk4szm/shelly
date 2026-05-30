<?php

namespace App\Model\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SettingsRequestPayload
{
    public function __construct(
        #[Assert\NotBlank]
        public string      $name,

        public bool|string $value,
    ) {}
}
