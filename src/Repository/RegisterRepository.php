<?php

namespace App\Repository; 

use App\Entity\Register;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class RegisterRepository extends ServiceEntityRepository 
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Register::class);
    }

    public function findByUserAndEvent(int $userId, int $eventId): ?Register
    {
    }
}