<?php

namespace App\Entity;

use App\Repository\RegisterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegisterRepository::class)]
#[ORM\Table(name: 'register')]
class Register
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'registers')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: false)]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'registers')]
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id_event', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\OneToMany(
        targetEntity: Registration::class, 
        mappedBy: 'register', 
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $registrations;

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
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

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
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
            $registration->setRegister($this);
        }
        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            if ($registration->getRegister() === $this) {
                $registration->setRegister(null);
            }
        }
        return $this;
    }
}