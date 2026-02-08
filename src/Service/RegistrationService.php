<?php
namespace App\Service;

use App\Entity\Registration;

class RegistrationService
{
    private UserService $userService;
    private EventService $eventService;

    public function __construct(UserService $userService, EventService $eventService)
    {
        $this->userService = $userService;
        $this->eventService = $eventService;
    }

    //Formatte une registration avec détails complets
     
    public function toArray(Registration $registration): array
    {
        $user = $registration->getUser();
        $event = $registration->getEvent();
        
        return [
            'id' => $registration->getId(),
            'registrationDate' => $registration->getRegistrationDate()?->setTimezone(new \DateTimeZone('Africa/Casablanca'))->format('Y-m-d H:i:s'),
            'status' => $registration->getStatus(),
            'user' => $this->userService->toArray($user),
            'event' => $this->eventService->toArray($event),
        ];
    }

    //Formatte une registration en version simple
     
    public function toArraySimple(Registration $registration): array
    {
        return [
            'id' => $registration->getId(),
            'registrationDate' => $registration->getRegistrationDate()?->setTimezone(new \DateTimeZone('Africa/Casablanca'))->format('Y-m-d H:i:s'),
            'status' => $registration->getStatus(),
            'userId' => $registration->getUser()->getId(),
            'eventId' => $registration->getEvent()->getId(),
            'eventTitle' => $registration->getEvent()->getTitle(),
        ];
    }

    //Formatte les statistiques d'un événement
     
    public function getEventStatistics(array $registrations): array
    {
        $stats = [
            'total' => count($registrations),
            'pending' => 0,
            'accepted' => 0,
            'rejected' => 0
        ];

        foreach ($registrations as $registration) {
            $status = strtolower($registration->getStatus());
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }

    //Vérifie si un utilisateur peut postuler à un événement
     
    public function canApply(Registration $registration = null, $event, $user): array
    {
        // Vérifier si l'utilisateur a déjà postulé
        if ($registration) {
            return [
                'can' => false,
                'reason' => 'You have already applied to this event',
                'currentStatus' => $registration->getStatus()
            ];
        }

        // Vérifier si c'est le créateur de l'événement
        if ($event->getUser()->getId() === $user->getId()) {
            return [
                'can' => false,
                'reason' => 'You cannot apply to your own event'
            ];
        }

        // Vérifier si l'événement est passé
        $now = new \DateTime('now', new \DateTimeZone('Africa/Casablanca'));
        if ($event->getStartDate() < $now) {
            return [
                'can' => false,
                'reason' => 'You cannot apply to a past event'
            ];
        }

        return ['can' => true];
    }
}