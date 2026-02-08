<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\UserRepository;
use App\Service\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class EventController extends AbstractController
{
    private EventService $eventService;
    private UserRepository $userRepository;

    public function __construct(EventService $eventService, UserRepository $userRepository)
    {
        $this->eventService = $eventService;
        $this->userRepository = $userRepository;
    }

    private function getJsonData(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }

    #[Route('/event/add', name: 'event_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);

        if (empty($data)) {
            return $this->json(['error' => 'Invalid JSON data.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['userId'])) {
            return $this->json(['error' => 'userId is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->find($data['userId']);

        if (!$user) {
            return $this->json(['error' => 'User not found with id: ' . $data['userId']], Response::HTTP_NOT_FOUND);
        }

        $requiredFields = ['title', 'startDate', 'endDate', 'location', 'category', 'capacity'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "Missing required field: $field"], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!is_string($data['title']) || !preg_match('/[a-zA-Z]/', $data['title'])) {
            return $this->json([
                'error' => 'Title must contain at least one letter'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['description']) && !empty($data['description'])) {
            if (!is_string($data['description']) || !preg_match('/[a-zA-Z]/', $data['description'])) {
                return $this->json([
                    'error' => 'Description must contain at least one letter'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!is_numeric($data['capacity']) || (int)$data['capacity'] <= 0) {
            return $this->json([
                'error' => 'Capacity must be a positive number'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $now = new \DateTime();
            
            // Minimum 24h à l'avance 
            $tomorrow = new \DateTime();
            $tomorrow->setTime(0, 0, 0);
            $tomorrow->modify('+1 day');
            
            $startDate = new \DateTime($data['startDate']);
            $endDate = new \DateTime($data['endDate']);

            //  Vérifier que l'événement est au moins demain
            if ($startDate < $tomorrow) {
                return $this->json([
                    'error' => 'Events must be created at least 24 hours in advance. Please select a date from tomorrow onwards.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier que l'événement dure au moins 30 minutes
            if ($endDate <= $startDate) {
                return $this->json([
                    'error' => 'End date must be after start date'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Calculer la différence en minutes de manière fiable
            $startTimestamp = $startDate->getTimestamp();
            $endTimestamp = $endDate->getTimestamp();
            $diffInMinutes = ($endTimestamp - $startTimestamp) / 60;
            
            if ($diffInMinutes < 30) {
                return $this->json([
                    'error' => 'End date must be at least 30 minutes after start date'
                ], Response::HTTP_BAD_REQUEST);
            }

            $event = new Event();
            $event->setTitle($data['title']);
            $event->setDescription($data['description'] ?? '');
            $event->setLocation($data['location']);
            $event->setCategory($data['category']);
            $event->setCapacity((int)$data['capacity']);
            $event->setImage($data['image'] ?? null);
            $event->setUser($user);
            $event->setStartDate($startDate);
            $event->setEndDate($endDate);

            $this->eventService->save($event);

            return $this->json([
                'message' => 'Event created successfully',
                'event' => $this->formatEvent($event)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create event: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/event/update/{id}', name: 'event_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $event = $this->eventService->findEventById($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->getJsonData($request);

        if (empty($data)) {
            return $this->json(['error' => 'No data provided'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['requestUserId'])) {
            if ($event->getUser()->getId() !== (int)$data['requestUserId']) {
                return $this->json([
                    'error' => 'Only the event owner can update this event'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        try {
            $now = new \DateTime();
            
            $tomorrow = new \DateTime();
            $tomorrow->setTime(0, 0, 0);
            $tomorrow->modify('+1 day');

            if (isset($data['title'])) {
                if (!is_string($data['title']) || !preg_match('/[a-zA-Z]/', $data['title'])) {
                    return $this->json([
                        'error' => 'Title must contain at least one letter'
                    ], Response::HTTP_BAD_REQUEST);
                }
                $event->setTitle($data['title']);
            }

            if (isset($data['description']) && !empty($data['description'])) {
                if (!is_string($data['description']) || !preg_match('/[a-zA-Z]/', $data['description'])) {
                    return $this->json([
                        'error' => 'Description must contain at least one letter'
                    ], Response::HTTP_BAD_REQUEST);
                }
                $event->setDescription($data['description']);
            }

            if (isset($data['location'])) $event->setLocation($data['location']);
            if (isset($data['category'])) $event->setCategory($data['category']);
            
            if (isset($data['capacity'])) {
                if (!is_numeric($data['capacity']) || (int)$data['capacity'] <= 0) {
                    return $this->json([
                        'error' => 'Capacity must be a positive number'
                    ], Response::HTTP_BAD_REQUEST);
                }
                $event->setCapacity((int)$data['capacity']);
            }

            if (isset($data['image'])) $event->setImage($data['image']);

            if (!empty($data['startDate'])) {
                $startDate = new \DateTime($data['startDate']);
                
                if ($startDate < $tomorrow) {
                    return $this->json([
                        'error' => 'Events must be scheduled at least 24 hours in advance. Please select a date from tomorrow onwards.'
                    ], Response::HTTP_BAD_REQUEST);
                }
                
                $event->setStartDate($startDate);
            }

            if (!empty($data['endDate'])) {
                $endDate = new \DateTime($data['endDate']);
                
                $currentStartDate = $event->getStartDate();
                if (!empty($data['startDate'])) {
                    $currentStartDate = new \DateTime($data['startDate']);
                }
                
                if ($endDate <= $currentStartDate) {
                    return $this->json([
                        'error' => 'End date must be after start date'
                    ], Response::HTTP_BAD_REQUEST);
                }

                // Calculer la différence en minutes de manière fiable
                $startTimestamp = $currentStartDate->getTimestamp();
                $endTimestamp = $endDate->getTimestamp();
                $diffInMinutes = ($endTimestamp - $startTimestamp) / 60;
                
                if ($diffInMinutes < 30) {
                    return $this->json([
                        'error' => 'End date must be at least 30 minutes after start date'
                    ], Response::HTTP_BAD_REQUEST);
                }
                
                $event->setEndDate($endDate);
            }

            $this->eventService->save($event);

            return $this->json([
                'message' => 'Event updated successfully',
                'event' => $this->formatEvent($event)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update event: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/event/delete/{id}', name: 'event_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $event = $this->eventService->findEventById($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->getJsonData($request);
        if (isset($data['userId'])) {
            if ($event->getUser()->getId() !== (int)$data['userId']) {
                return $this->json([
                    'error' => 'Only the event owner can delete this event'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $this->eventService->remove($event);

        return $this->json(['message' => 'Event deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/event/{id}', name: 'event_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $event = $this->eventService->findEventById($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->formatEventWithDetails($event));
    }

    #[Route('/events', name: 'event_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $events = $this->eventService->findAllEvents();
        $eventsData = array_map([$this, 'formatEvent'], $events);

        return $this->json($eventsData);
    }

    #[Route('/events/upcoming', name: 'event_upcoming', methods: ['GET'])]
    public function upcoming(): JsonResponse
    {
        $events = $this->eventService->findUpcomingEvents();
        return $this->json(array_map([$this, 'formatEvent'], $events));
    }

    #[Route('/events/category/{category}', name: 'event_by_category', methods: ['GET'])]
    public function byCategory(string $category): JsonResponse
    {
        $events = $this->eventService->findByCategory($category);
        return $this->json(array_map([$this, 'formatEvent'], $events));
    }

    #[Route('/events/user/{userId}', name: 'event_by_user', methods: ['GET'])]
    public function byUser(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $events = $this->eventService->findByUser($userId);
        return $this->json(array_map([$this, 'formatEvent'], $events));
    }

    #[Route('/events/search', name: 'event_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (empty($query)) {
            return $this->json(['error' => 'Search query is required'], Response::HTTP_BAD_REQUEST);
        }

        $events = $this->eventService->search($query);
        return $this->json(array_map([$this, 'formatEvent'], $events));
    }

    #[Route('/events/past', name: 'event_past', methods: ['GET'])]
    public function past(): JsonResponse
    {
        $events = $this->eventService->findPastEvents();
        return $this->json(array_map([$this, 'formatEvent'], $events));
    }

    #[Route('/events/available', name: 'event_available', methods: ['GET'])]
    public function available(): JsonResponse
    {
        $events = $this->eventService->findAvailableEvents();
        return $this->json(array_map([$this, 'formatEvent'], $events));
    }

    private function formatEvent(Event $event): array
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
            'availableSlots' => $this->eventService->getAvailableSlots($event),
            'isPast' => $this->eventService->isPastEvent($event),
            'owner' => [
                'id' => $event->getUser()?->getId(),
                'firstName' => $event->getUser()?->getFirstName(),
                'lastName' => $event->getUser()?->getLastName(),
                'email' => $event->getUser()?->getEmail(),
            ]
        ];
    }

    private function formatEventWithDetails(Event $event): array
    {
        $formatted = $this->formatEvent($event);
        
        $registrations = $event->getRegistrations();
        $acceptedCount = 0;
        $pendingCount = 0;
        
        foreach ($registrations as $registration) {
            if ($registration->getStatus() === 'accepted') {
                $acceptedCount++;
            } elseif ($registration->getStatus() === 'pending') {
                $pendingCount++;
            }
        }
        
        $formatted['statistics'] = [
            'totalRegistrations' => count($registrations),
            'acceptedParticipants' => $acceptedCount,
            'pendingApplications' => $pendingCount,
            'availableSlots' => $event->getCapacity() - $acceptedCount
        ];

        return $formatted;
    }
}