<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // Trouve un utilisateur par email
     
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //Pagination des utilisateurs
    
    public function findAllPaginated(int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // Recherche par nom ou email
    
    public function search(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.firstName LIKE :query OR u.lastName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //Utilisateurs avec leurs statistiques
     
    public function findWithStats(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'COUNT(DISTINCT e.id) as eventsCount', 'COUNT(DISTINCT c.id) as commentsCount')
            ->leftJoin('u.createdEvents', 'e')
            ->leftJoin('u.comments', 'c')
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();
    }

    //Utilisateurs actifs (qui ont créé des événements)
    
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.createdEvents', 'e')
            ->groupBy('u.id')
            ->having('COUNT(e.id) > 0')
            ->getQuery()
            ->getResult();
    }
}