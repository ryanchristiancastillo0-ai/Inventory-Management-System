<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;

class UserController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // FIND USER BY ID
    public function findUserById(int $id): ?array
    {
        return $this->userService->findUserById($id);
    }

    // FIND USER BY EMAIL
    public function findUserByEmail(string $email): ?array
    {
        return $this->userService->findUserByEmail($email);
    }

    // CREATE USER
    public function createUser(
        string $username,
        string $password,
        string $email
    ): bool {
        if (
            trim($username) === "" ||
            $password === "" ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return false;
        }

        // HASH PASSWORD BEFORE SAVING
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->userService->createUser(
            $username,
            $passwordHash,
            $email
        );

        return $result !== null && $result !== false;
    }

    // UPDATE USER BY ID
    public function updateUserById(
        int $id,
        string $username,
        string $password,
        string $email
    ): bool {
        if (
            $id <= 0 ||
            trim($username) === "" ||
            $password === "" ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->userService->updateUserById(
            $id,
            $username,
            $passwordHash,
            $email
        );

        return $result !== null && $result !== false;
    }

    // DELETE USER BY ID
    public function deleteUserById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $result = $this->userService->deleteUserById($id);

        return $result !== null && $result !== false;
    }
}
