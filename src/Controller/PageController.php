<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/events-page', name: 'app_events_page')]
    public function eventsPage(): Response
    {
        return $this->render('event/index.html.twig');
    }
    
    #[Route('/event-details/{id}', name: 'app_event_details')]
    public function eventDetails(int $id): Response
    {
       
        return $this->render('event/details.html.twig', [
            'eventId' => $id
        ]);
    }
    #[Route('/event-details/{id}', name: 'event_details_page', methods: ['GET'])]
public function detailsPage(int $id): Response
{
    return $this->render('event/details.html.twig', [
        'eventId' => $id
    ]);
}
#[Route('/myevents', name: 'my_events_page', methods: ['GET'])]
public function myEventsPage(): Response
{
    return $this->render('event/myevents.html.twig');
}

#[Route('/myregister', name: 'my_registrations_page', methods: ['GET'])]
public function myRegistrationsPage(): Response
{
    return $this->render('event/my_registrations.html.twig');
}

}