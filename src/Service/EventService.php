<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class EventService
{
    private EntityManagerInterface $em;
    private EventRepository $eventRepository;

    public function __construct(EntityManagerInterface $em, EventRepository $eventRepository)
    {
        $this->em = $em;
        $this->eventRepository = $eventRepository;
    }

    public function findEventById(int $id): ?Event
    {
        return $this->eventRepository->find($id);
    }

    public function findAllEvents(): array
    {
        return $this->eventRepository->findAll();
    }

    //Trouve les événements à venir
    
    public function findUpcomingEvents(): array
    {
        return $this->eventRepository->findUpcomingEvents();
    }

    //Trouve les événements par catégorie
     
    public function findByCategory(string $category): array
    {
        return $this->eventRepository->findByCategory($category);
    }

    //Trouve les événements d'un utilisateur
     
    public function findByUser(int $userId): array
    {
        return $this->eventRepository->findByUser($userId);
    }

    //Recherche d'événements
     
    public function search(string $query): array
    {
        return $this->eventRepository->search($query);
    }

    //Vérifie si un événement a des places disponibles
     
    public function hasAvailableSlots(Event $event): bool
    {
        $acceptedCount = 0;
        foreach ($event->getRegistrations() as $registration) {
            if ($registration->getStatus() === 'accepted') {
                $acceptedCount++;
            }
        }
        return $acceptedCount < $event->getCapacity();
    }

    //Obtient le nombre de places restantes
     
    public function getAvailableSlots(Event $event): int
    {
        $acceptedCount = 0;
        foreach ($event->getRegistrations() as $registration) {
            if ($registration->getStatus() === 'accepted') {
                $acceptedCount++;
            }
        }
        return max(0, $event->getCapacity() - $acceptedCount);
    }

    //Vérifie si un événement est passé

    public function isPastEvent(Event $event): bool
    {
        return $event->getEndDate() < new \DateTime();
    }

    //Vérifie si un utilisateur peut s'inscrire
     
    public function canUserRegister(Event $event, int $userId): array
    {
        // Vérifier si l'événement est passé
        if ($this->isPastEvent($event)) {
            return ['can' => false, 'reason' => 'Event has already ended'];
        }

        // Vérifier s'il reste des places
        if (!$this->hasAvailableSlots($event)) {
            return ['can' => false, 'reason' => 'Event is full'];
        }

        // Vérifier si l'utilisateur est déjà inscrit
        foreach ($event->getRegistrations() as $registration) {
            if ($registration->getUser()->getId() === $userId) {
                return ['can' => false, 'reason' => 'Already registered'];
            }
        }

        return ['can' => true];
    }

    public function save(Event $event): void
    {
        if ($event->getId() === null) {
            $this->em->persist($event);
        }
        $this->em->flush();
    }

    public function remove(Event $event): void
    {
        $this->em->remove($event);
        $this->em->flush();
    }

    /*Formatte un événement en tableau*/
    public function toArray(Event $event): array
    {
        return [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'startDate' => $event->getStartDate()?->format('Y-m-d H:i:s'),
            'endDate' => $event->getEndDate()?->format('Y-m-d H:i:s'),
            'location' => $event->getLocation(),
            'category' => $event->getCategory(),
            'capacity' => $event->getCapacity(),
            'image' => $event->getImage(),
            'availableSlots' => $this->getAvailableSlots($event),
            'isPast' => $this->isPastEvent($event),
            'owner' => [
                'id' => $event->getUser()?->getId(),
                'firstName' => $event->getUser()?->getFirstName(),
                'lastName' => $event->getUser()?->getLastName(),
                'email' => $event->getUser()?->getEmail(),
            ]
        ];
    }
 
public function findPastEvents(): array
{
    return $this->eventRepository->findPastEvents();
}

public function findAvailableEvents(): array
{
    return $this->eventRepository->findAvailableEvents();
}
}