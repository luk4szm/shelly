<?php

declare(strict_types=1);

namespace App\Service\AirQuality;

/**
 * Class for calculating the Apparent Temperature (AT) or 'Perceived Temperature'
 * based on the ambient air temperature, relative humidity, and wind speed.
 * It uses a logic that selects between Wind Chill (WCT), Apparent Temperature (AT),
 * and Heat Index (HI) formulas depending on the input temperature.
 */
class PerceivedTemperatureCalculator
{
    private float $airTemperatureCelsius;
    private float $relativeHumidityPercent;
    private float $windSpeedMetersPerSecond;

    /**
     * Constructor for the PerceivedTemperatureCalculator class.
     *
     * @param float $airTemperatureCelsius Temperature of the air in degrees Celsius (°C).
     * @param float $relativeHumidityPercent Relative humidity in percent (%).
     * @param float $windSpeedMetersPerSecond Wind speed in meters per second (m/s).
     */
    public function __construct(
        float $airTemperatureCelsius,
        float $relativeHumidityPercent,
        float $windSpeedMetersPerSecond
    ) {
        $this->airTemperatureCelsius = $airTemperatureCelsius;
        // Constrain RH to 0-100%, but use a minimal value for calculations if zero.
        $this->relativeHumidityPercent = max(0.0001, min(100.0, $relativeHumidityPercent));
        $this->windSpeedMetersPerSecond = $windSpeedMetersPerSecond;
    }

    /**
     * Calculates the Perceived Temperature (°C) by selecting the appropriate
     * formula (HI, AT, or WCT) based on the air temperature.
     *
     * @return float The perceived temperature in degrees Celsius.
     */
    public function calculatePerceivedTemperature(): float
    {
        $T = $this->airTemperatureCelsius;
        $RH = $this->relativeHumidityPercent;
        $V = $this->windSpeedMetersPerSecond;

        // 1. Heat Index (HI) Formula - for T > 26.67°C
        if ($T > 26.67) {
            return $this->calculateHeatIndex($T, $RH);
        }

        // 2. Apparent Temperature (AT) Formula - for 10.0°C <= T <= 26.67°C
        if ($T >= 10.0) {
            // Case 1: T > 25°C
            if ($T > 25.0) {
                if ($V > 3.0) {
                    return $this->calculateApparentTemperature($T, $RH, $V);
                }
                return $T;
            }

            // Case 2: 10°C <= T <= 25°C
            if ($V > 2.0) {
                $effectiveRH = $RH;
                // Special condition: if T < 15°C and RH <= 60%, use RH = 60% for AT calculation.
                if ($T < 15.0 && $RH <= 60.0) {
                    $effectiveRH = 60.0;
                }
                return $this->calculateApparentTemperature($T, $effectiveRH, $V);
            }
            return $T; // Return T if wind is too low (V <= 2 m/s).
        }

        // 3. Wind Chill Temperature (WCT) Formula - for T < 10.0°C
        return $this->calculateWindChillTemperature($T, $V);
    }

    // --- PRIVATE CALCULATION METHODS ---

    /**
     * Calculates the Saturated Vapour Pressure (VP) in hPa based on T and RH.
     *
     * @param float $T Air temperature in °C.
     * @param float $RH Relative humidity in %.
     * @return float Vapour pressure (VP).
     */
    private function calculateVapourPressureFromRh(float $T, float $RH): float
    {
        // Formula used in the original JS script (approximation):
        return 6.112 * (10 ** (7.5 * $T / (237.7 + $T))) * 0.01 * $RH;
    }

    /**
     * Calculates the Heat Index (HI) value in °C.
     * It uses the HI formula from the script (based on Fahrenheit) and returns max(HI, T).
     *
     * @param float $T Air temperature in °C.
     * @param float $RH Relative humidity in %.
     * @return float Heat Index in °C (corrected to be no lower than T).
     */
    private function calculateHeatIndex(float $T, float $RH): float
    {
        // 1. Convert T to Fahrenheit (°F)
        $T_Fahrenheit = ($T * 1.8) + 32;

        // 2. Calculate HI in °F (HI_F) using the complex formula
        $Tf = $T_Fahrenheit;
        $RH_percent = $RH;

        $HI_Fahrenheit = (-42.379
            + (2.04901523 * $Tf)
            + (10.14333127 * $RH_percent)
            - (0.22475541 * $Tf * $RH_percent)
            - (0.00683783 * pow($Tf, 2))
            - (0.05481717 * pow($RH_percent, 2))
            + (0.00122874 * pow($Tf, 2) * $RH_percent)
            + (0.00085282 * $Tf * pow($RH_percent, 2))
            - (0.00000199 * pow($Tf, 2) * pow($RH_percent, 2))
        );

        // 3. Convert HI back to °C
        $HI_Celsius = ($HI_Fahrenheit - 32) / 1.8;

        // 4. Correction: HI must not be lower than the actual air temperature T
        return max($HI_Celsius, $T);
    }

    /**
     * Calculates the Apparent Temperature (AT) in °C.
     *
     * @param float $T Air temperature in °C.
     * @param float $RH Effective Relative humidity in %.
     * @param float $V Wind speed in m/s.
     * @return float Apparent Temperature in °C.
     */
    private function calculateApparentTemperature(float $T, float $RH, float $V): float
    {
        // Calculate the Vapour Pressure (VP)
        $vapourPressure = $this->calculateVapourPressureFromRh($T, $RH);

        // AT Formula: TO = T + 0.33 * VP - 0.7 * V - 4.0
        return $T + 0.33 * $vapourPressure - 0.7 * $V - 4.0;
    }

    /**
     * Calculates the Wind Chill Temperature (WCT) in °C.
     * Returns min(WCT, T).
     *
     * @param float $T Air temperature in °C.
     * @param float $V Wind speed in m/s.
     * @return float Wind Chill Temperature in °C (corrected to be no higher than T).
     */
    private function calculateWindChillTemperature(float $T, float $V): float
    {
        // WCT formula requires wind speed in km/h (3.6 * V)
        $windSpeedKmH = 3.6 * $V;

        $WCT = (13.12
            + 0.6215 * $T
            - 11.37 * pow($windSpeedKmH, 0.16)
            + 0.3965 * $T * pow($windSpeedKmH, 0.16)
        );

        // Correction: WCT must not be higher than the actual air temperature T
        return min($WCT, $T);
    }
}
