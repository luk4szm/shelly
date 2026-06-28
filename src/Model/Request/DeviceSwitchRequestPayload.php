<?php

namespace App\Model\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class DeviceSwitchRequestPayload
{
    public function __construct(
        #[Assert\NotBlank] public string                       $deviceId,
        public int                                             $channel = 0,
        #[Assert\Choice(choices: ['on', 'off'])] public string $action,
    ) {}
}
