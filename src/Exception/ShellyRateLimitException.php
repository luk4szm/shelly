<?php

namespace App\Exception;

final class ShellyRateLimitException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Shelly Cloud: limit żądań nadal przekroczony po 3 ponowieniach. Spróbuj ponownie później.', 429);
    }
}
