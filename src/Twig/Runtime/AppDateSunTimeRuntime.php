<?php

namespace App\Twig\Runtime;

use App\Model\DateSunTime;
use App\Service\Date\DateSunInfo;
use Twig\Extension\RuntimeExtensionInterface;

class AppDateSunTimeRuntime implements RuntimeExtensionInterface
{
    public function getSunriseHour(?string $date = null): string
    {
        $date = new \DateTime($date ?? null);

        return DateSunInfo::get($date, DateSunTime::SUNRISE)->format("H:i");
    }

    public function getSunsetHour(?string $date = null): string
    {
        $date = new \DateTime($date ?? null);

        return DateSunInfo::get($date, DateSunTime::SUNSET)->format("H:i");
    }
}
