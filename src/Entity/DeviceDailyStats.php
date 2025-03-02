<?php

namespace App\Entity;

use App\Repository\DeviceDailyStatsRepository;
use App\Utils\TimeUtils;
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
    private ?float $energy = 0;

    #[ORM\Column]
    private ?int $inclusions = 0;

    #[ORM\Column]
    /** in seconds */
    private ?int $longestRunTime = 0;

    #[ORM\Column]
    /** in seconds */
    private ?int $longestPauseTime = 0;

    #[ORM\Column]
    /** in seconds */
    private ?int $totalActiveTime = 0;

    public function __construct(
        string            $device,
        DateTimeInterface $date,
        ?float            $energy = null,
        ?int              $inclusions = null,
        ?int              $longestRunTime = null,
        ?int              $longestPauseTime = null,
        ?int              $totalActiveTime = null,
    ) {
        $this->device = $device;
        $this->date   = $date;

        $this->setEnergy($energy)
            ->setInclusions($inclusions)
            ->setLongestRunTime($longestRunTime)
            ->setLongestPauseTime($longestPauseTime)
            ->setTotalActiveTime($totalActiveTime);
    }

    public function paste(self $dailyStats) : self
    {
        $this->setEnergy($dailyStats->getEnergy())
             ->setInclusions($dailyStats->getInclusions())
             ->setLongestRunTime($dailyStats->getLongestRunTime())
             ->setLongestPauseTime($dailyStats->getLongestPauseTime())
             ->setTotalActiveTime($dailyStats->getTotalActiveTime());

        return $this;
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

    public function setEnergy(?float $energy): static
    {
        $this->energy = $energy;

        return $this;
    }

    public function getInclusions(): ?int
    {
        return $this->inclusions;
    }

    public function setInclusions(?int $inclusions): static
    {
        $this->inclusions = $inclusions;

        return $this;
    }

    public function getLongestRunTime(): ?int
    {
        return $this->longestRunTime;
    }

    public function getLongestRunTimeReadable(): string
    {
        return TimeUtils::getReadableTime($this->longestRunTime);
    }

    public function setLongestRunTime(?int $longestRunTime): static
    {
        $this->longestRunTime = $longestRunTime;

        return $this;
    }

    public function getLongestPauseTime(): ?int
    {
        return $this->longestPauseTime;
    }

    public function getLongestPauseTimeReadable(): string
    {
        return TimeUtils::getReadableTime($this->longestPauseTime);
    }

    public function setLongestPauseTime(?int $longestPauseTime): static
    {
        $this->longestPauseTime = $longestPauseTime;

        return $this;
    }

    public function getTotalActiveTime(): ?int
    {
        return $this->totalActiveTime;
    }

    public function getTotalActiveTimeReadable(): string
    {
        return TimeUtils::getReadableTime($this->totalActiveTime);
    }

    public function setTotalActiveTime(?int $totalActiveTime): static
    {
        $this->totalActiveTime = $totalActiveTime;

        return $this;
    }
}
