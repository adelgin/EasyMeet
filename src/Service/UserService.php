<?php

namespace App\Service;

use App\Repository\UserRepository;

class UserService
{
    public function __construct(public UserRepository $userRepository) {}

    public function getAll(): array {
        return $this->userRepository->findAll();
    }
}
