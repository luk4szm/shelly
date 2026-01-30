<?php

namespace App\Entity;

use App\Repository\SmsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SmsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Sms
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $phoneNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $developer = null;

    #[ORM\Column]
    private ?float $cost = null;

    #[ORM\Column]
    private array $raport = [];

    #[ORM\Column]
    private ?bool $isTest = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhoneNumber(): ?int
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(int $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getDeveloper(): ?string
    {
        return $this->developer;
    }

    public function setDeveloper(?string $developer): static
    {
        $this->developer = $developer;

        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(float $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getRaport(): array
    {
        return $this->raport;
    }

    public function setRaport(array $raport): static
    {
        $this->raport = $raport;

        return $this;
    }

    public function isTest(): ?bool
    {
        return $this->isTest;
    }

    public function setIsTest(bool $isTest): static
    {
        $this->isTest = $isTest;

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
