<?php

namespace App\Utils;

class TimeUtils
{
    public static function getReadableTime(int $seconds): string
    {
        return gmdate("G:i:s", $seconds);
    }
}
