<?php

namespace App\Model\Weather;

enum SymbolCode: string
{
    case CLEARSKY_DAY = '01d';
    case CLEARSKY_NIGHT = '01n';
    case FAIR_DAY = '02d';
    case FAIR_NIGHT = '02n';
    case PARTLYCLOUDY_DAY = '03d';
    case PARTLYCLOUDY_NIGHT = '03n';
    case CLOUDY = '04';
    case LIGHTRAIN = '46';
    case RAIN = '09';
    case HEAVYRAIN = '10';
    case LIGHTSLEET = '47';
    case SLEET = '12';
    case HEAVYSLEET = '48';
    case LIGHTSNOW = '49';
    case SNOW = '13';
    case HEAVYSNOW = '50';
    case LIGHTRAINSHOWERS_DAY = '40d';
    case LIGHTRAINSHOWERS_NIGHT = '40n';
    case RAINSHOWERS_DAY = '05d';
    case RAINSHOWERS_NIGHT = '05n';
    case HEAVYRAINSHOWERS_DAY = '41d';
    case HEAVYRAINSHOWERS_NIGHT = '41n';
    case LIGHTSLEETSHOWERS_DAY = '42d';
    case LIGHTSLEETSHOWERS_NIGHT = '42n';
    case SLEETSHOWERS_DAY = '07d';
    case SLEETSHOWERS_NIGHT = '07n';
    case HEAVYSLEETSHOWERS_DAY = '43d';
    case HEAVYSLEETSHOWERS_NIGHT = '43n';
    case LIGHTRAINSHOWERSANDTHUNDER_DAY = '24d';
    case LIGHTRAINSHOWERSANDTHUNDER_NIGHT = '24n';
    case RAINSHOWERSANDTHUNDER_DAY = '06d';
    case RAINSHOWERSANDTHUNDER_NIGHT = '06n';
    case HEAVYRAINSHOWERSANDTHUNDER_DAY = '25d';
    case HEAVYRAINSHOWERSANDTHUNDER_NIGHT = '25n';
    case LIGHTSLEETSHOWERSANDTHUNDER_DAY = '26d';
    case LIGHTSLEETSHOWERSANDTHUNDER_NIGHT = '26n';
    case SLEETSHOWERSANDTHUNDER_DAY = '20d';
    case SLEETSHOWERSANDTHUNDER_NIGHT = '20n';
    case HEAVYSLEETSHOWERSANDTHUNDER_DAY = '27d';
    case HEAVYSLEETSHOWERSANDTHUNDER_NIGHT = '27n';
    case LIGHTSNOWSHOWERSANDTHUNDER_DAY = '28d';
    case LIGHTSNOWSHOWERSANDTHUNDER_NIGHT = '28n';
    case SNOWSHOWERSANDTHUNDER_DAY = '21d';
    case SNOWSHOWERSANDTHUNDER_NIGHT = '21n';
    case HEAVYSNOWSHOWERSANDTHUNDER_DAY = '29d';
    case HEAVYSNOWSHOWERSANDTHUNDER_NIGHT = '29n';
    case LIGHTRAINANDTHUNDER = '30';
    case RAINANDTHUNDER = '22';
    case HEAVYRAINANDTHUNDER = '11';
    case LIGHTSLEETANDTHUNDER = '31';
    case SLEETANDTHUNDER = '23';
    case HEAVYSLEETANDTHUNDER = '32';
    case LIGHTSNOWANDTHUNDER = '33';
    case SNOWANDTHUNDER = '14';
    case HEAVYSNOWANDTHUNDER = '34';
    case FOG = '15';

    public static function tryFromName(string $name): ?SymbolCode
    {
        foreach (self::cases() as $case) {
            if ($case->name === strtoupper($name)) {
                return $case;
            }
        }

        return null;
    }

    public static function valueFromName(string $name): string
    {
        $case = self::tryFromName($name);

        if (null === $case) {
            throw new \InvalidArgumentException(sprintf('Nieznana nazwa enuma: "%s".', $name));
        }

        return $case->value;
    }
}
