<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'id_user')]
    private ?int $id = null;

    #[ORM\Column(name: 'last_name', length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'first_name', length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(name: 'password')]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $createdEvents;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(targetEntity: Register::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $registers;

    //Relation avec Registration
    #[ORM\OneToMany(targetEntity: Registration::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $registrations;

    public function __construct()
    {
        $this->createdEvents = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->registers = new ArrayCollection();
        $this->registrations = new ArrayCollection(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        // $this->plainPassword = null;
    }

    // CREATED EVENTS 
    public function getCreatedEvents(): Collection
    {
        return $this->createdEvents;
    }

    public function addCreatedEvent(Event $event): static
    {
        if (!$this->createdEvents->contains($event)) {
            $this->createdEvents->add($event);
            $event->setUser($this);
        }
        return $this;
    }

    public function removeCreatedEvent(Event $event): static
    {
        if ($this->createdEvents->removeElement($event)) {
            if ($event->getUser() === $this) {
                $event->setUser(null);
            }
        }
        return $this;
    }

    // COMMENTS 
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUser($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }
        return $this;
    }

    // REGISTERS 
    public function getRegisters(): Collection
    {
        return $this->registers;
    }

    public function addRegister(Register $register): static
    {
        if (!$this->registers->contains($register)) {
            $this->registers->add($register);
            $register->setUser($this);
        }
        return $this;
    }

    public function removeRegister(Register $register): static
    {
        if ($this->registers->removeElement($register)) {
            if ($register->getUser() === $this) {
                $register->setUser(null);
            }
        }
        return $this;
    }

    
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setUser($this);
        }
        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            if ($registration->getUser() === $this) {
                $registration->setUser(null);
            }
        }
        return $this;
    }
}