<?php

namespace App\Repository;

use App\Entity\Registration;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    //Trouve les registrations par statut
     
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.registrationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //Trouve les registrations d'un utilisateur avec filtre de statut
     
    public function findByUserAndStatus(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.registrationDate', 'DESC');

        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    // Trouve les registrations d'un événement avec filtre de statut
    
    public function findByEventAndStatus(Event $event, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.registrationDate', 'ASC');

        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    //Compte les participants acceptés pour un événement
     
    public function countAcceptedByEvent(Event $event): int
    {
        return $this->count([
            'event' => $event,
            'status' => 'accepted'
        ]);
    }

    // Trouve les registrations en attente pour les événements d'un utilisateur
     
    public function findPendingForUserEvents(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->andWhere('e.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'pending')
            ->orderBy('r.registrationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

 
    public function hasUserApplied(User $user, Event $event): bool
    {
        return $this->count([
            'user' => $user,
            'event' => $event
        ]) > 0;
    }

    //Registrations récentes (dernières 24h)
    
    public function findRecent(): array
    {
        $yesterday = new \DateTime('-24 hours');

        return $this->createQueryBuilder('r')
            ->andWhere('r.registrationDate > :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->orderBy('r.registrationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
