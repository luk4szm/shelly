<?php

namespace App\Entity\Process;

use App\Repository\Process\RecurringProcessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecurringProcessRepository::class)]
class RecurringProcess extends Process
{
    #[ORM\Column]
    private ?bool $isActive = null;

    /** @var int|null Time in minutes between process runs */
    #[ORM\Column(nullable: true)]
    private ?int $timeInterval = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $lastRunAt = null;

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getTimeInterval(): ?int
    {
        return $this->timeInterval;
    }

    public function setTimeInterval(?int $timeInterval): static
    {
        $this->timeInterval = $timeInterval;

        return $this;
    }

    public function getLastRunAt(): ?\DateTime
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(?\DateTime $lastRunAt): static
    {
        $this->lastRunAt = $lastRunAt;

        return $this;
    }
}
