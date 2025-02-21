<?php

namespace App\Entity;

use App\Repository\DeviceDailyStatsRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceDailyStatsRepository::class)]
class DeviceDailyStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date;

    #[ORM\Column(length: 255)]
    private ?string $device;

    #[ORM\Column]
    /** Energy in Wh */
    private ?float $energy;

    #[ORM\Column]
    private ?int $inclusions;

    #[ORM\Column]
    /** in seconds */
    private ?int $longestRunTime;

    #[ORM\Column]
    /** in seconds */
    private ?int $longestPauseTime;

    #[ORM\Column]
    /** in seconds */
    private ?int $totalActiveTime;

    public function __construct(
        string            $device,
        DateTimeInterface $date,
        float             $energy,
        int               $inclusions,
        int               $longestRunTime,
        int               $longestPauseTime,
        int               $totalActiveTime
    ) {
        $this->device           = $device;
        $this->date             = $date;
        $this->energy           = $energy;
        $this->inclusions       = $inclusions;
        $this->longestRunTime   = $longestRunTime;
        $this->longestPauseTime = $longestPauseTime;
        $this->totalActiveTime  = $totalActiveTime;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(string $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getEnergy(): ?float
    {
        return $this->energy;
    }

    public function setEnergy(float $energy): static
    {
        $this->energy = $energy;

        return $this;
    }

    public function getInclusions(): ?int
    {
        return $this->inclusions;
    }

    public function setInclusions(int $inclusions): static
    {
        $this->inclusions = $inclusions;

        return $this;
    }

    public function getLongestRunTime(): ?int
    {
        return $this->longestRunTime;
    }

    public function setLongestRunTime(int $longestRunTime): static
    {
        $this->longestRunTime = $longestRunTime;

        return $this;
    }

    public function getLongestPauseTime(): ?int
    {
        return $this->longestPauseTime;
    }

    public function setLongestPauseTime(int $longestPauseTime): static
    {
        $this->longestPauseTime = $longestPauseTime;

        return $this;
    }

    public function getTotalActiveTime(): ?int
    {
        return $this->totalActiveTime;
    }

    public function setTotalActiveTime(int $totalActiveTime): static
    {
        $this->totalActiveTime = $totalActiveTime;

        return $this;
    }
}
