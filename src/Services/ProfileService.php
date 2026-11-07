<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepo;
use App\Exceptions\ProfileValidationException;
use InvalidArgumentException;
use PDOException;

class ProfileService
{
    public function __construct(private UserRepo $userRepo)
    {
    }

    public function getProfile(int $userId): ?array
    {
        return $userId > 0 ? ($this->userRepo->getProfileById($userId) ?: null) : null;
    }

    public function updateProfile(int $userId, string $username, string $email): bool
    {
        $username = trim($username);
        $email = strtolower(trim($email));

        $errors = [];
        // Match the existing users table: varchar(50) and varchar(150).
        if ($username === '' || mb_strlen($username) > 50) {
            $errors['username'] = 'Username must contain 1 to 50 characters.';
        }
        if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address of up to 150 characters.';
        }
        if ($errors) {
            throw new ProfileValidationException($errors);
        }
        if ($this->getProfile($userId) === null) {
            throw new InvalidArgumentException('Account not found.');
        }

        $existing = $this->userRepo->findUserByUsername($username);
        if ($existing && (int) $existing['id'] !== $userId) {
            $errors['username'] = 'Username is already in use.';
        }
        $existing = $this->userRepo->findUserByEmail($email);
        if ($existing && (int) $existing['id'] !== $userId) {
            $errors['email'] = 'Email address is already in use.';
        }
        if ($errors) {
            throw new ProfileValidationException($errors);
        }

        try {
            return $this->userRepo->updateProfile($userId, $username, $email);
        } catch (PDOException $error) {
            // Also handle a duplicate created between validation and the write.
            if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new InvalidArgumentException('Username or email address is already in use.', 0, $error);
            }
            throw $error;
        }
    }

    public function getSecuritySummary(int $userId): ?array
    {
        return $userId > 0 ? ($this->userRepo->getProfileSecuritySummary($userId) ?: null) : null;
    }
}
