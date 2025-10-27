<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?int $phoneNumber = null;

    /**
     * @var Collection<int, HeatingNote>
     */
    #[ORM\OneToMany(targetEntity: HeatingNote::class, mappedBy: 'createdBy', orphanRemoval: true)]
    private Collection $heatingNotes;

    /**
     * @var Collection<int, UserNotification>
     */
    #[ORM\OneToMany(targetEntity: UserNotification::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userNotifications;

    public function __construct()
    {
        $this->heatingNotes      = new ArrayCollection();
        $this->userNotifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    /**
     * @return Collection<int, HeatingNote>
     */
    public function getHeatingNotes(): Collection
    {
        return $this->heatingNotes;
    }

    public function addHeatingNote(HeatingNote $heatingNote): static
    {
        if (!$this->heatingNotes->contains($heatingNote)) {
            $this->heatingNotes->add($heatingNote);
            $heatingNote->setCreatedBy($this);
        }

        return $this;
    }

    public function removeHeatingNote(HeatingNote $heatingNote): static
    {
        if ($this->heatingNotes->removeElement($heatingNote)) {
            // set the owning side to null (unless already changed)
            if ($heatingNote->getCreatedBy() === $this) {
                $heatingNote->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserNotification>
     */
    public function getUserNotifications(): Collection
    {
        return $this->userNotifications;
    }

    public function addUserNotification(UserNotification $userNotification): static
    {
        if (!$this->userNotifications->contains($userNotification)) {
            $this->userNotifications->add($userNotification);
            $userNotification->setUser($this);
        }

        return $this;
    }

    public function removeUserNotification(UserNotification $userNotification): static
    {
        if ($this->userNotifications->removeElement($userNotification)) {
            // set the owning side to null (unless already changed)
            if ($userNotification->getUser() === $this) {
                $userNotification->setUser(null);
            }
        }

        return $this;
    }
}
