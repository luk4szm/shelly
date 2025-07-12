<?php

namespace App\Entity;

use App\Repository\HookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HookRepository::class)]
#[ORM\Index(name: 'device_property_idx', columns: ['device', 'property'])]
#[ORM\HasLifecycleCallbacks]
class Hook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $device;

    #[ORM\Column(length: 255)]
    private ?string $property;

    #[ORM\Column(length: 255)]
    private ?string $value;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct(string $device, string $property, string $value)
    {
        $this->device   = $device;
        $this->property = $property;
        $this->value    = $value;
    }

    public function __clone(): void
    {
        $this->id = null;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProperty(): ?string
    {
        return $this->property;
    }

    public function setProperty(string $property): static
    {
        $this->property = $property;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

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
