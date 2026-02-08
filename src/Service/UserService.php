<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    //Convertit un User en tableau simple (sans relations)
     
    public function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $this->getFullName($user),
            'email' => $user->getEmail(),
            'photo' => $user->getPhoto(),
            'roles' => $user->getRoles(),
        ];
    }

    //Convertit un User en tableau avec détails (avec relations)
    
    public function toArrayWithDetails(User $user): array
    {
        return [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $this->getFullName($user),
            'email' => $user->getEmail(),
            'photo' => $user->getPhoto(),
            'roles' => $user->getRoles(),
            'statistics' => [
                'eventsCreated' => $user->getCreatedEvents()->count(),
                'comments' => $user->getComments()->count(),
                'registrations' => $user->getRegistrations()->count(),
            ]
        ];
    }

    //Crée un nouvel utilisateur
     
    public function createUser(array $data, UserPasswordHasherInterface $passwordHasher): User
    {
        $user = new User();
        
        // Définir les informations de base
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setEmail($data['email']);
        
        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        
        // Photo optionnelle
        if (isset($data['photo'])) {
            $user->setPhoto($data['photo']);
        }

        // Définir le rôle par défaut
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        return $user;
    }

    //Met à jour un utilisateur existant
    
    public function updateUser(
        User $user, 
        array $data, 
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): void {
        // Mise à jour du prénom
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        
        // Mise à jour du nom
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        
        // Mise à jour de l'email (avec vérification d'unicité)
        if (isset($data['email'])) {
            $existingUser = $userRepository->findOneByEmail($data['email']);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                throw new \InvalidArgumentException('Email already exists');
            }
            $user->setEmail($data['email']);
        }
        
        // Mise à jour du mot de passe
        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }
        
        // Mise à jour de la photo
        if (isset($data['photo'])) {
            $user->setPhoto($data['photo']);
        }

        // Mise à jour des rôles
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }
    }

    //Retourne le nom complet de l'utilisateur
     
    public function getFullName(User $user): string
    {
        return trim($user->getFirstName() . ' ' . $user->getLastName());
    }

    // Vérifie si un utilisateur peut être supprimé
     
    public function canBeDeleted(User $user): bool
    {
        return $user->getCreatedEvents()->isEmpty() 
            && $user->getComments()->isEmpty()
            && $user->getRegistrations()->isEmpty();
    }
}