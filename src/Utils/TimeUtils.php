<?php

namespace App\Utils;

class TimeUtils
{
    public static function getReadableTime(int $seconds): string
    {
        return date("G:i:s", $seconds);
    }
}
