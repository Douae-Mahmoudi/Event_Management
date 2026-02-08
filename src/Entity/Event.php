<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_event')]
    private ?int $id = null;

    #[ORM\Column(name: 'title', length: 200)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'start_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: 'location', length: 255)]
    private ?string $location = null;

    #[ORM\Column(name: 'category', length: 100)]
    private ?string $category = null;

    #[ORM\Column(name: 'capacity')]
    private ?int $capacity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'createdEvents')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(
        targetEntity: Comment::class, 
        mappedBy: 'event', 
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $comments;

    #[ORM\OneToMany(
        targetEntity: Register::class, 
        mappedBy: 'event', 
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $registers;

    #[ORM\OneToMany(
        targetEntity: Registration::class, 
        mappedBy: 'event', 
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $registrations;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->registers = new ArrayCollection();
        $this->registrations = new ArrayCollection(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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
            $comment->setEvent($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getEvent() === $this) {
                $comment->setEvent(null);
            }
        }
        return $this;
    }

    public function getRegisters(): Collection
    {
        return $this->registers;
    }

    public function addRegister(Register $register): static
    {
        if (!$this->registers->contains($register)) {
            $this->registers->add($register);
            $register->setEvent($this);
        }
        return $this;
    }

    public function removeRegister(Register $register): static
    {
        if ($this->registers->removeElement($register)) {
            if ($register->getEvent() === $this) {
                $register->setEvent(null);
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
            $registration->setEvent($this);
        }
        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            if ($registration->getEvent() === $this) {
                $registration->setEvent(null);
            }
        }
        return $this;
    }
}