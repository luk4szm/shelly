<?php

namespace App\Entity;

use App\Repository\GasMeterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GasMeterRepository::class)]
#[ORM\HasLifecycleCallbacks]
class GasMeter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $indication;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    public function __construct(float $indication)
    {
        $this->indication = $indication;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIndication(): ?float
    {
        return $this->indication;
    }

    public function setIndication(float $indication): static
    {
        $this->indication = $indication;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
