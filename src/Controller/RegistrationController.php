<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Repository\RegistrationRepository;
use App\Repository\UserRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/registrations')]
class RegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RegistrationRepository $registrationRepository,
        private UserRepository $userRepository,
        private EventRepository $eventRepository,
    ) {}

    // POSTULER À UN ÉVÉNEMENT
    #[Route('/apply', name: 'registration_apply', methods: ['POST'])]
    public function apply(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (!isset($data['userId']) || !isset($data['eventId'])) {
            return $this->json([
                'error' => 'userId and eventId are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->find($data['userId']);
        $event = $this->eventRepository->find($data['eventId']);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        //  Vérifier si l'utilisateur est le créateur de l'événement
        if ($event->getUser()->getId() === $user->getId()) {
            return $this->json([
                'error' => 'You cannot apply to your own event'
            ], Response::HTTP_FORBIDDEN);
        }

        // Vérifier si l'utilisateur a déjà postulé
        $existingRegistration = $this->registrationRepository->findOneBy([
            'user' => $user,
            'event' => $event
        ]);

        if ($existingRegistration) {
            return $this->json([
                'error' => 'You have already applied to this event',
                'currentStatus' => $existingRegistration->getStatus()
            ], Response::HTTP_CONFLICT);
        }

        // Vérifier si l'événement a atteint sa capacité maximale
        $acceptedRegistrations = $this->registrationRepository->count([
            'event' => $event,
            'status' => 'accepted'
        ]);

        if ($acceptedRegistrations >= $event->getCapacity()) {
            return $this->json([
                'error' => 'This event has reached its maximum capacity',
                'capacity' => $event->getCapacity(),
                'currentParticipants' => $acceptedRegistrations
            ], Response::HTTP_CONFLICT);
        }

        //   Vérifier si l'événement est déjà passé
        $now = new \DateTime();
        if ($event->getStartDate() < $now) {
            return $this->json([
                'error' => 'You cannot apply to a past event'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Créer la registration
        $registration = new Registration();
        $registration->setUser($user);
        $registration->setEvent($event);

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Application submitted successfully',
            'registration' => [
                'id' => $registration->getId(),
                'userId' => $user->getId(),
                'eventId' => $event->getId(),
                'status' => $registration->getStatus(),
                'registrationDate' => $registration->getRegistrationDate()->format('Y-m-d H:i:s')
            ]
        ], Response::HTTP_CREATED);
    }

   // LISTE DES INSCRIPTIONS D'UN UTILISATEUR - AVEC FILTRE

#[Route('/user/{userId}', name: 'registration_by_user', methods: ['GET'])]

public function getByUser(int $userId, Request $request): JsonResponse

{

    $user = $this->userRepository->find($userId);



    if (!$user) {

        return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);

    }

    $statusFilter = $request->query->get('status'); // pending, accepted, rejected
    // Construire les critères de recherche

    $criteria = ['user' => $user];

    if ($statusFilter && in_array($statusFilter, ['pending', 'accepted', 'rejected'])) {

        $criteria['status'] = $statusFilter;

    }



    $registrations = $this->registrationRepository->findBy($criteria, ['registrationDate' => 'DESC']);

    $data = [];



    // Calculer les statistiques

    $stats = [

        'total' => 0,

        'pending' => 0,

        'accepted' => 0,

        'rejected' => 0

    ];



    // Récupérer TOUTES les registrations pour les stats (sans filtre)

    $allRegistrations = $this->registrationRepository->findBy(['user' => $user]);

    foreach ($allRegistrations as $reg) {

        $stats['total']++;

        $stats[strtolower($reg->getStatus())]++;

    }



    foreach ($registrations as $registration) {

        $data[] = [

            'id' => $registration->getId(),

            'event' => [

                'id' => $registration->getEvent()->getId(),

                'title' => $registration->getEvent()->getTitle(),

                'description' => $registration->getEvent()->getDescription(),

                'startDate' => $registration->getEvent()->getStartDate()->format('Y-m-d H:i:s'),

                'endDate' => $registration->getEvent()->getEndDate()->format('Y-m-d H:i:s'),

                'location' => $registration->getEvent()->getLocation(),

                'category' => $registration->getEvent()->getCategory(),

                'image' => $registration->getEvent()->getImage(),

            ],

            'status' => $registration->getStatus(),

            'registrationDate' => $registration->getRegistrationDate()->format('Y-m-d H:i:s')

        ];

    }



    return $this->json([

        'statistics' => $stats,

        'filter' => $statusFilter ?? 'all',

        'count' => count($data),

        'registrations' => $data

    ]);

}

    // LISTE DES PARTICIPANTS D'UN ÉVÉNEMENT
    #[Route('/event/{eventId}', name: 'registration_by_event', methods: ['GET'])]
    public function getByEvent(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->find($eventId);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $registrations = $this->registrationRepository->findBy(['event' => $event], ['registrationDate' => 'ASC']);
        $data = [];

        // Statistiques
        $stats = [
            'total' => count($registrations),
            'pending' => 0,
            'accepted' => 0,
            'rejected' => 0
        ];

        foreach ($registrations as $registration) {
            // Compter les statuts
            $stats[strtolower($registration->getStatus())]++;

            $data[] = [
                'id' => $registration->getId(),
                'user' => [
                    'id' => $registration->getUser()->getId(),
                    'firstName' => $registration->getUser()->getFirstName(),
                    'lastName' => $registration->getUser()->getLastName(),
                    'email' => $registration->getUser()->getEmail(),
                    'photo' => $registration->getUser()->getPhoto(),
                ],
                'status' => $registration->getStatus(),
                'registrationDate' => $registration->getRegistrationDate()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json([
            'statistics' => $stats,
            'capacity' => $event->getCapacity(),
            'availableSlots' => $event->getCapacity() - $stats['accepted'],
            'registrations' => $data
        ]);
    }

    #[Route('/{id}/status', name: 'registration_update_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $registration = $this->registrationRepository->find($id);

        if (!$registration) {
            return $this->json(['error' => 'Registration not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId'])) {
            return $this->json([
                'error' => 'userId is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $event = $registration->getEvent();
        if ($event->getUser()->getId() !== (int)$data['userId']) {
            return $this->json([
                'error' => 'Only the event owner can accept or reject registrations'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!isset($data['status']) || !in_array($data['status'], ['pending', 'accepted', 'rejected'])) {
            return $this->json([
                'error' => 'Invalid status. Must be: pending, accepted, or rejected'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($data['status'] === 'accepted') {
            $acceptedCount = $this->registrationRepository->count([
                'event' => $event,
                'status' => 'accepted'
            ]);

            if ($acceptedCount >= $event->getCapacity()) {
                return $this->json([
                    'error' => 'Cannot accept: event has reached maximum capacity',
                    'capacity' => $event->getCapacity(),
                    'currentParticipants' => $acceptedCount
                ], Response::HTTP_CONFLICT);
            }
        }

        $registration->setStatus($data['status']);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Status updated successfully',
            'registration' => [
                'id' => $registration->getId(),
                'status' => $registration->getStatus(),
                'candidate' => [
                    'id' => $registration->getUser()->getId(),
                    'firstName' => $registration->getUser()->getFirstName(),
                    'lastName' => $registration->getUser()->getLastName(),
                    'email' => $registration->getUser()->getEmail(),
                ],
                'event' => [
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                ]
            ]
        ]);
    }

    // ANNULER UNE INSCRIPTION
    #[Route('/{id}', name: 'registration_cancel', methods: ['DELETE'])]
    public function cancel(int $id): JsonResponse
    {
        $registration = $this->registrationRepository->find($id);

        if (!$registration) {
            return $this->json(['error' => 'Registration not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($registration);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Registration cancelled successfully'
        ]);
    }

    // TOUTES LES INSCRIPTIONS
    #[Route('', name: 'registration_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $registrations = $this->registrationRepository->findBy([], ['registrationDate' => 'DESC']);
        $data = [];

        foreach ($registrations as $registration) {
            $data[] = [
                'id' => $registration->getId(),
                'user' => [
                    'id' => $registration->getUser()->getId(),
                    'firstName' => $registration->getUser()->getFirstName(),
                    'lastName' => $registration->getUser()->getLastName(),
                    'email' => $registration->getUser()->getEmail(),
                ],
                'event' => [
                    'id' => $registration->getEvent()->getId(),
                    'title' => $registration->getEvent()->getTitle(),
                    'startDate' => $registration->getEvent()->getStartDate()->format('Y-m-d H:i:s'),
                ],
                'status' => $registration->getStatus(),
                'registrationDate' => $registration->getRegistrationDate()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json($data);
    }

    // VOIR LES CANDIDATURES POUR MES ÉVÉNEMENTS (propriétaire)
    #[Route('/my-events/{userId}', name: 'registration_my_events', methods: ['GET'])]
    public function getMyEventsRegistrations(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer tous les événements créés par cet utilisateur
        $myEvents = $this->eventRepository->findBy(['user' => $user]);

        if (empty($myEvents)) {
            return $this->json([
                'message' => 'You have not created any events yet',
                'totalEvents' => 0,
                'events' => []
            ]);
        }

        $data = [];

        foreach ($myEvents as $event) {
            // Récupérer toutes les candidatures pour cet événement
            $registrations = $this->registrationRepository->findBy(
                ['event' => $event],
                ['registrationDate' => 'DESC']
            );

            // Statistiques
            $stats = [
                'total' => count($registrations),
                'pending' => 0,
                'accepted' => 0,
                'rejected' => 0
            ];

            $candidates = [];

            foreach ($registrations as $registration) {
                $stats[strtolower($registration->getStatus())]++;

                $candidates[] = [
                    'registrationId' => $registration->getId(),
                    'candidate' => [
                        'id' => $registration->getUser()->getId(),
                        'firstName' => $registration->getUser()->getFirstName(),
                        'lastName' => $registration->getUser()->getLastName(),
                        'email' => $registration->getUser()->getEmail(),
                        'photo' => $registration->getUser()->getPhoto(),
                    ],
                    'status' => $registration->getStatus(),
                    'appliedAt' => $registration->getRegistrationDate()->format('Y-m-d H:i:s')
                ];
            }

            $data[] = [
                'event' => [
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'description' => $event->getDescription(),
                    'startDate' => $event->getStartDate()->format('Y-m-d H:i:s'),
                    'endDate' => $event->getEndDate()->format('Y-m-d H:i:s'),
                    'location' => $event->getLocation(),
                    'category' => $event->getCategory(),
                    'capacity' => $event->getCapacity(),
                    'image' => $event->getImage(),
                ],
                'statistics' => $stats,
                'availableSlots' => $event->getCapacity() - $stats['accepted'],
                'candidates' => $candidates
            ];
        }

        return $this->json([
            'message' => 'Your events with all candidates',
            'totalEvents' => count($myEvents),
            'events' => $data
        ]);
    }
}