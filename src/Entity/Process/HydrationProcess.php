<?php

namespace App\Entity\Process;

use App\Repository\Process\ScheduledProcessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduledProcessRepository::class)]
class HydrationProcess extends ScheduledProcess
{
    #[ORM\Column(length: 255)]
    private ?string $valve = null;

    #[ORM\Column]
    private ?int $duration = null;

    public function getValve(): ?string
    {
        return $this->valve;
    }

    public function setValve(string $valve): static
    {
        $this->valve = $valve;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }
}
