<?php

namespace App\Entity;

use App\Repository\AirQualityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AirQualityRepository::class)]
#[ORM\Index(name: 'air_quality_idx', columns: ['measured_at'])]
#[ORM\HasLifecycleCallbacks]
class AirQuality
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $sensor = null;

    #[ORM\Column]
    private ?float $pm25 = null;

    #[ORM\Column]
    private ?float $pm10 = null;

    #[ORM\Column(nullable: true)]
    private ?float $temperature = null;

    #[ORM\Column(nullable: true)]
    private ?float $perceivedTemperature = null;

    #[ORM\Column(nullable: true)]
    private ?float $pressure = null;

    #[ORM\Column(nullable: true)]
    private ?float $seaLevelPressure = null;

    #[ORM\Column(nullable: true)]
    private ?float $humidity = null;

    #[ORM\Column]
    private ?\DateTime $measuredAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function pre(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSensor(): ?int
    {
        return $this->sensor;
    }

    public function setSensor(int $sensor): static
    {
        $this->sensor = $sensor;

        return $this;
    }

    public function getPm25(): ?float
    {
        return $this->pm25;
    }

    public function setPm25(float $pm25): static
    {
        $this->pm25 = $pm25;

        return $this;
    }

    public function getPm10(): ?float
    {
        return $this->pm10;
    }

    public function setPm10(float $pm10): static
    {
        $this->pm10 = $pm10;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getPerceivedTemperature(): ?float
    {
        return $this->perceivedTemperature;
    }

    public function setPerceivedTemperature(?float $perceivedTemperature): static
    {
        $this->perceivedTemperature = $perceivedTemperature;

        return $this;
    }

    public function getPressure(): ?float
    {
        return $this->pressure;
    }

    public function setPressure(?float $pressure): static
    {
        $this->pressure = $pressure;

        return $this;
    }

    public function getSeaLevelPressure(): ?float
    {
        return $this->seaLevelPressure;
    }

    public function setSeaLevelPressure(?float $seaLevelPressure): static
    {
        $this->seaLevelPressure = $seaLevelPressure;

        return $this;
    }

    public function calculateSeaLevelPressure(int $altitude): void
    {
        if ($this->pressure === null) {
            return;
        }

        // 2. Define the simplified barometric constant 'C'.
        // This constant is derived from: C = (g * M) / R
        // g ≈ 9.80665 m/s² (gravity)
        // M ≈ 0.0289644 kg/mol (molar mass of air)
        // R ≈ 8.314 J/(mol*K) (universal gas constant)
        // C ≈ 0.03416 [1/m*K]
        $barometricConstant = 0.03416;

        // 1. Convert temperature from Celsius to Kelvin (T_K = T_C + 273.15)
        $temperatureKelvin = $this->temperature + 273.15;

        if ($temperatureKelvin === null || $temperatureKelvin <= 0) {
            // Prevent division by zero or non-physical negative temperatures
            $this->seaLevelPressure = 0.0;

            return;
        }

        // 3. Apply the barometric formula: P₀ = P_abs * exp( (C * h) / T_K )
        $exponent = ($barometricConstant * $altitude) / $temperatureKelvin;

        $seaLevelPressure = $this->pressure * exp($exponent);

        // Round the result for typical meteorological precision (two decimal places)
        $this->seaLevelPressure = round($seaLevelPressure, 2);
    }

    public function getHumidity(): ?float
    {
        return $this->humidity;
    }

    public function setHumidity(?float $humidity): static
    {
        $this->humidity = $humidity;

        return $this;
    }

    public function getMeasuredAt(): ?\DateTime
    {
        return $this->measuredAt;
    }

    public function setMeasuredAt(\DateTime $measuredAt): static
    {
        $this->measuredAt = $measuredAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
