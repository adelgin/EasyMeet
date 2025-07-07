<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, UserService $userService): Response
    {
        $users = count($userService->getAll());

        return $this->render('user/index.html.twig', [
            'users_count' => $users,
        ]);
    }
}
