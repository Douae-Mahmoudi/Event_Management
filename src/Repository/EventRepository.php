<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

   
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.category = :category')
            ->setParameter('category', $category)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //Trouve les événements à venir
    
    public function findUpcomingEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //Trouve les événements passés
    public function findPastEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.endDate < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.endDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //Trouve les événements créés par un utilisateur
    
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //Recherche d'événements (titre, description, location)
     
    public function search(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.title LIKE :query OR e.description LIKE :query OR e.location LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //Événements avec places disponibles
     
    public function findAvailableEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.registrations', 'r', 'WITH', 'r.status = :accepted')
            ->setParameter('accepted', 'accepted')
            ->groupBy('e.id')
            ->having('COUNT(r.id) < e.capacity')
            ->andWhere('e.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //Pagination des événements
    public function findPaginated(int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.startDate', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}