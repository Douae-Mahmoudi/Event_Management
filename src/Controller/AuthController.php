<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('auth/home.html.twig');
    }

    #[Route('/login', name: 'app_login_page')]
    public function loginPage(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/register', name: 'app_register_page')]
    public function registerPage(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('auth/dashboard.html.twig');
    }
    #[Route('/events-page', name: 'app_events_page')]
public function eventsPage(): Response
{
    return $this->render('events/index.html.twig');
}
#[Route('/profile', name: 'profile_page', methods: ['GET'])]
    public function profilePage(): Response
    {
        return $this->render('auth/profile.html.twig');
    }
  #[Route('/forgot-password', name: 'app_forgot_password_page')]
public function forgotPassword(): Response
{
    return $this->render('auth/forgot_password.html.twig');  
}
}