<?php

namespace App\Utils;

class TimeUtils
{
    public static function getReadableTime(int $seconds): string
    {
        $days    = floor($seconds / 86400);
        $seconds %= 86400;

        $hours   = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return $days > 0
            ? sprintf('%dd %d:%02d:%02d', $days, $hours, $minutes, $seconds)
            : sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
