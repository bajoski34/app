<?php

namespace App\Repository;

use Symfony\Component\Security\Core\User\InMemoryUserProvider;

class InMemoryUserRepository
{

    private InMemoryUserProvider $userEntity;

    public function __construct()
    {
        $this->userEntity = new InMemoryUserProvider;
    }

    /**
     * create a new in memory user
     */
    public function add(string $username, string $password): void
    {
        new InMemoryUserProvider([
            $username => [
                'password' => $password,
                'roles' => ['ROLE_USER'],
            ],
        ]);
    }

    public function findUserByUsername(string $username): \Symfony\Component\Security\Core\User\UserInterface
    {
        return $this->userEntity->loadUserByIdentifier($username);
    }
}