<?php

namespace App\Entity;

use App\Repository\WeatherForecastRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeatherForecastRepository::class)]
#[ORM\HasLifecycleCallbacks]
class WeatherForecast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $time = null;

    #[ORM\Column]
    private ?float $temperature = null;

    #[ORM\Column(nullable: true)]
    private ?float $precipitation = null;

    #[ORM\Column]
    private ?float $airPressure = null;

    #[ORM\Column]
    private ?float $humidity = null;

    #[ORM\Column]
    private ?float $windSpeed = null;

    #[ORM\Column]
    private ?float $windDirection = null;

    #[ORM\Column]
    private ?float $clouds = null;

    #[ORM\Column]
    private ?float $cloudsLow = null;

    #[ORM\Column]
    private ?float $cloudsMedium = null;

    #[ORM\Column]
    private ?float $cloudsHigh = null;

    #[ORM\Column]
    private ?float $uvIndex = null;

    #[ORM\Column]
    private ?float $fog = null;

    #[ORM\Column]
    private ?float $dewPointTemperature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $symbolCode = null;

    #[ORM\Column(nullable: true)]
    private ?int $sunlightFactor = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function pre(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getPrecipitation(): ?float
    {
        return $this->precipitation;
    }

    public function setPrecipitation(?float $precipitation): static
    {
        $this->precipitation = $precipitation;

        return $this;
    }

    public function getAirPressure(): ?float
    {
        return $this->airPressure;
    }

    public function setAirPressure(float $airPressure): static
    {
        $this->airPressure = $airPressure;

        return $this;
    }

    public function getHumidity(): ?float
    {
        return $this->humidity;
    }

    public function setHumidity(float $humidity): static
    {
        $this->humidity = $humidity;

        return $this;
    }

    public function getWindSpeed(): ?float
    {
        return $this->windSpeed;
    }

    public function setWindSpeed(float $windSpeed): static
    {
        $this->windSpeed = $windSpeed;

        return $this;
    }

    public function getWindDirection(): ?float
    {
        return $this->windDirection;
    }

    public function setWindDirection(float $windDirection): static
    {
        $this->windDirection = $windDirection;

        return $this;
    }

    public function getClouds(): ?float
    {
        return $this->clouds;
    }

    public function setClouds(float $clouds): static
    {
        $this->clouds = $clouds;

        return $this;
    }

    public function getCloudsLow(): ?float
    {
        return $this->cloudsLow;
    }

    public function setCloudsLow(float $cloudsLow): static
    {
        $this->cloudsLow = $cloudsLow;

        return $this;
    }

    public function getCloudsMedium(): ?float
    {
        return $this->cloudsMedium;
    }

    public function setCloudsMedium(float $cloudsMedium): static
    {
        $this->cloudsMedium = $cloudsMedium;

        return $this;
    }

    public function getCloudsHigh(): ?float
    {
        return $this->cloudsHigh;
    }

    public function setCloudsHigh(float $cloudsHigh): static
    {
        $this->cloudsHigh = $cloudsHigh;

        return $this;
    }

    public function getUvIndex(): ?float
    {
        return $this->uvIndex;
    }

    public function setUvIndex(float $uvIndex): static
    {
        $this->uvIndex = $uvIndex;

        return $this;
    }

    public function getFog(): ?float
    {
        return $this->fog;
    }

    public function setFog(float $fog): static
    {
        $this->fog = $fog;

        return $this;
    }

    public function getDewPointTemperature(): ?float
    {
        return $this->dewPointTemperature;
    }

    public function setDewPointTemperature(float $dewPointTemperature): static
    {
        $this->dewPointTemperature = $dewPointTemperature;

        return $this;
    }

    public function getSymbolCode(): ?string
    {
        return $this->symbolCode;
    }

    public function setSymbolCode(?string $symbolCode): static
    {
        $this->symbolCode = $symbolCode;

        return $this;
    }

    public function getSunlightFactor(): ?int
    {
        return $this->sunlightFactor;
    }

    public function setSunlightFactor(?int $sunlightFactor): static
    {
        $this->sunlightFactor = $sunlightFactor;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
