<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
        private UserService $userService,
    ) {}

    //LOGIN
    #[Route('/login', name: 'user_login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des champs obligatoires
            if (empty($data['email']) || empty($data['password'])) {
                return $this->json([
                    'error' => 'Email and password are required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Rechercher l'utilisateur par email
            $user = $this->userRepository->findOneByEmail($data['email']);

            // Vérifier les identifiants
            if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
                return $this->json([
                    'error' => 'Invalid credentials'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Retourner les infos utilisateur
            return $this->json([
                'message' => 'Login successful',
                'user' => $this->userService->toArray($user)
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // lISTE (avec pagination)
    #[Route('', name: 'user_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        try {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = min(100, $request->query->getInt('limit', 20));
            
            $users = $this->userRepository->findAllPaginated($page, $limit);
            $total = $this->userRepository->count([]);
            
            return $this->json([
                'users' => array_map([$this->userService, 'toArray'], $users),
                'page' => $page,
                'limit' => $limit,
                'total' => $total
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch users: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //CRÉER UN UTILISATEUR (avec validations renforcées)
    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            //Validation des champs obligatoires
            if (empty($data['email']) || empty($data['password'])) {
                return $this->json([
                    'error' => 'Email and password are required'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (empty($data['firstName']) || empty($data['lastName'])) {
                return $this->json([
                    'error' => 'First name and last name are required'
                ], Response::HTTP_BAD_REQUEST);
            }

            //First Name doit contenir au moins une lettre
            if (!is_string($data['firstName']) || !preg_match('/[a-zA-Z]/', $data['firstName'])) {
                return $this->json([
                    'error' => 'First name must contain at least one letter'
                ], Response::HTTP_BAD_REQUEST);
            }

            //  Last Name doit contenir au moins une lettre
            if (!is_string($data['lastName']) || !preg_match('/[a-zA-Z]/', $data['lastName'])) {
                return $this->json([
                    'error' => 'Last name must contain at least one letter'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Format email valide
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json([
                    'error' => 'Invalid email format'
                ], Response::HTTP_BAD_REQUEST);
            }

            //Email doit se terminer par @gmail.com
            if (!str_ends_with(strtolower($data['email']), '@gmail.com')) {
                return $this->json([
                    'error' => 'Email must be a Gmail address (@gmail.com)'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'email existe déjà
            if ($this->userRepository->findOneByEmail($data['email'])) {
                return $this->json([
                    'error' => 'Email already exists'
                ], Response::HTTP_CONFLICT);
            }

            // Longueur minimale du mot de passe (8 caractères)
            if (strlen($data['password']) < 8) {
                return $this->json([
                    'error' => 'Password must be at least 8 characters long'
                ], Response::HTTP_BAD_REQUEST);
            }

            //  Au moins une majuscule
            if (!preg_match('/[A-Z]/', $data['password'])) {
                return $this->json([
                    'error' => 'Password must contain at least one uppercase letter'
                ], Response::HTTP_BAD_REQUEST);
            }

            //  Au moins une minuscule
            if (!preg_match('/[a-z]/', $data['password'])) {
                return $this->json([
                    'error' => 'Password must contain at least one lowercase letter'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Au moins un chiffre
            if (!preg_match('/[0-9]/', $data['password'])) {
                return $this->json([
                    'error' => 'Password must contain at least one number'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Au moins un caractère spécial
            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data['password'])) {
                return $this->json([
                    'error' => 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>)'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Créer l'utilisateur via le service
            $user = $this->userService->createUser($data, $passwordHasher);

            // Valider avec Symfony Validator
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'error' => implode(', ', $errorMessages)
                ], Response::HTTP_BAD_REQUEST);
            }

            // Sauvegarder en base de données
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'User created successfully',
                'user' => $this->userService->toArray($user)
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // AFFICHER UN UTILISATEUR
    #[Route('/{id}', name: 'user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);

            if (!$user) {
                return $this->json([
                    'error' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json($this->userService->toArrayWithDetails($user));
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // METTRE À JOUR UN UTILISATEUR
    #[Route('/{id}', name: 'user_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);

            if (!$user) {
                return $this->json([
                    'error' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['firstName']) && !empty($data['firstName'])) {
                if (!is_string($data['firstName']) || !preg_match('/[a-zA-Z]/', $data['firstName'])) {
                    return $this->json([
                        'error' => 'First name must contain at least one letter'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            if (isset($data['lastName']) && !empty($data['lastName'])) {
                if (!is_string($data['lastName']) || !preg_match('/[a-zA-Z]/', $data['lastName'])) {
                    return $this->json([
                        'error' => 'Last name must contain at least one letter'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            if (isset($data['email']) && !empty($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->json([
                        'error' => 'Invalid email format'
                    ], Response::HTTP_BAD_REQUEST);
                }

                if (!str_ends_with(strtolower($data['email']), '@gmail.com')) {
                    return $this->json([
                        'error' => 'Email must be a Gmail address (@gmail.com)'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $this->userService->updateUser($user, $data, $passwordHasher, $this->userRepository);

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'error' => implode(', ', $errorMessages)
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'User updated successfully',
                'user' => $this->userService->toArray($user)
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

   // SUPPRIMER UN UTILISATEUR (avec suppression automatique des dépendances)
#[Route('/{id}', name: 'user_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
public function delete(int $id): JsonResponse
{
    try {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }


        // Supprimer tous les commentaires de l'utilisateur
        foreach ($user->getComments() as $comment) {
            $this->entityManager->remove($comment);
        }

        // Supprimer toutes les inscriptions (registrations) de l'utilisateur
        foreach ($user->getRegistrations() as $registration) {
            $this->entityManager->remove($registration);
        }

        // Supprimer tous les événements créés par l'utilisateur
        foreach ($user->getCreatedEvents() as $event) {
            // Supprimer les registrations de cet événement
            foreach ($event->getRegistrations() as $registration) {
                $this->entityManager->remove($registration);
            }
            
            // Supprimer les commentaires de cet événement
            foreach ($event->getComments() as $comment) {
                $this->entityManager->remove($comment);
            }
            
            // Supprimer l'événement
            $this->entityManager->remove($event);
        }

        // Enfin, supprimer l'utilisateur
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User and all associated data deleted successfully'
        ]);
        
    } catch (\Exception $e) {
        return $this->json([
            'error' => 'Failed to delete user: ' . $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    //DÉCONNEXION
    #[Route('/logout', name: 'user_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json([
            'message' => 'Logout successful'
        ], Response::HTTP_OK);
    }

    // CHANGER LE MOT DE PASSE (avec validations renforcées)
    #[Route('/profile/change-password', name: 'user_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (empty($data['userId']) || 
                empty($data['currentPassword']) || 
                empty($data['newPassword']) || 
                empty($data['confirmPassword'])) {
                return $this->json([
                    'error' => 'All fields are required (userId, currentPassword, newPassword, confirmPassword)'
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->userRepository->find($data['userId']);
            
            if (!$user) {
                return $this->json([
                    'error' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // érifier que les nouveaux mots de passe correspondent
            if ($data['newPassword'] !== $data['confirmPassword']) {
                return $this->json([
                    'error' => 'New password and confirmation do not match'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier l'ancien mot de passe
            if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                return $this->json([
                    'error' => 'Current password is incorrect'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // VALIDATION: Longueur minimale (8 caractères)
            if (strlen($data['newPassword']) < 8) {
                return $this->json([
                    'error' => 'New password must be at least 8 characters long'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!preg_match('/[A-Z]/', $data['newPassword'])) {
                return $this->json([
                    'error' => 'Password must contain at least one uppercase letter'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!preg_match('/[a-z]/', $data['newPassword'])) {
                return $this->json([
                    'error' => 'Password must contain at least one lowercase letter'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!preg_match('/[0-9]/', $data['newPassword'])) {
                return $this->json([
                    'error' => 'Password must contain at least one number'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data['newPassword'])) {
                return $this->json([
                    'error' => 'Password must contain at least one special character'
                ], Response::HTTP_BAD_REQUEST);
            }

            //  Changer le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);
            
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Password changed successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to change password: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    // MOT DE PASSE OUBLIÉ 
#[Route('/forgot-password', name: 'user_forgot_password', methods: ['POST'])]
public function forgotPassword(
    Request $request, 
    UserPasswordHasherInterface $passwordHasher
): JsonResponse {
    try {
        $data = json_decode($request->getContent(), true);

        // Validation des champs obligatoires
        if (empty($data['email'])) {
            return $this->json([
                'error' => 'Email is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['newPassword']) || empty($data['confirmPassword'])) {
            return $this->json([
                'error' => 'New password and confirmation are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'email existe dans la base de données
        $user = $this->userRepository->findOneByEmail($data['email']);
        
        if (!$user) {
            return $this->json([
                'error' => 'No account found with this email address'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que les nouveaux mots de passe correspondent
        if ($data['newPassword'] !== $data['confirmPassword']) {
            return $this->json([
                'error' => 'New password and confirmation do not match'
            ], Response::HTTP_BAD_REQUEST);
        }        
        // Longueur minimale (8 caractères)
        if (strlen($data['newPassword']) < 8) {
            return $this->json([
                'error' => 'Password must be at least 8 characters long'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Au moins une majuscule
        if (!preg_match('/[A-Z]/', $data['newPassword'])) {
            return $this->json([
                'error' => 'Password must contain at least one uppercase letter'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Au moins une minuscule
        if (!preg_match('/[a-z]/', $data['newPassword'])) {
            return $this->json([
                'error' => 'Password must contain at least one lowercase letter'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $data['newPassword'])) {
            return $this->json([
                'error' => 'Password must contain at least one number'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Au moins un caractère spécial
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data['newPassword'])) {
            return $this->json([
                'error' => 'Password must contain at least one special character'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Réinitialiser le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Password reset successfully'
        ], Response::HTTP_OK);
        
    } catch (\Exception $e) {
        return $this->json([
            'error' => 'Failed to reset password: ' . $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
}