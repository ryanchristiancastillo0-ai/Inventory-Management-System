<?php

namespace App\Services;

use App\Repositories\AuthRepo;
use InvalidArgumentException;

class AuthService
{
    private AuthRepo $authRepo;

    public function __construct(AuthRepo $authRepo)
    {
        $this->authRepo = $authRepo;
        $this->startSession();
    }

    // =============================
    // SESSION
    // =============================

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // =============================
    // REGISTER
    // =============================

    public function register(
        string $username,
        string $email,
        string $password
    ): bool {
        $username = trim($username);
        $email = strtolower(trim($email));

        if (
            $username === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            throw new InvalidArgumentException(
                "Please enter a valid username and email."
            );
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException(
                "Password must be at least 8 characters."
            );
        }

        $existingUser = $this->authRepo
            ->findUserByEmail($email);

        if ($existingUser) {
            throw new InvalidArgumentException(
                "Email is already registered."
            );
        }

        // Check username BEFORE the INSERT: usernames are unique and
        // case-insensitive, so a taken name would otherwise surface as
        // a raw duplicate-key PDOException ("Registration failed").
        $existingUsername = $this->authRepo
            ->findUserByUsername($username);

        if ($existingUsername) {
            throw new InvalidArgumentException(
                "Username is already taken. Please choose another."
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        return $this->authRepo->createUser(
            $username,
            $email,
            $passwordHash
        );
    }

    // =============================
    // LOGIN
    // =============================

    public function login(
        string $email,
        string $password
    ): bool {
        $email = strtolower(trim($email));

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $password === ''
        ) {
            return false;
        }

        $user = $this->authRepo
            ->findUserByEmail($email);

        if (!$user) {
            return false;
        }

        // Google-only users may not have a password.
        if (empty($user['password_hash'])) {
            return false;
        }

        if (
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {
            return false;
        }

        // Deactivated accounts cannot log in, even with a
        // correct password.
        if (isset($user['is_active']) && (int) $user['is_active'] !== 1) {
            throw new InvalidArgumentException(
                "Your account has been deactivated. Contact an administrator."
            );
        }

        $this->createLoginSession($user);

        return true;
    }

    // =============================
    // AUTHENTICATED SESSION
    // =============================

    private function createLoginSession(array $user): void
    {
        $this->startSession();

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
    }

    // =============================
    // CHECK LOGIN
    // =============================

    public function isAuthenticated(): bool
    {
        $this->startSession();

        return !empty($_SESSION['logged_in'])
            && !empty($_SESSION['user_id']);
    }

    // =============================
    // ACCOUNT STILL ACTIVE?
    //
    // A deactivated account must lose its session at once,
    // not only be blocked on the next login attempt.
    // Callers that are not logged in are unaffected.
    // =============================

    public function isActiveSession(): bool
    {
        $this->startSession();

        if (empty($_SESSION['user_id'])) {
            return true; // nothing to lock out
        }

        $user = $this->authRepo->findUserById(
            (int) $_SESSION['user_id']
        );

        if (!$user) {
            return false;
        }

        return (int) ($user['is_active'] ?? 1) === 1;
    }

    // =============================
    // CHECK USER ROLE
    // =============================

    public function hasRole(string $role): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $userRole = $_SESSION['role'] ?? 'USER';
        $requested = strtoupper($role);
        $actual = strtoupper($userRole);

        // Strict role checking: ADMIN cannot access USER-gated pages and vice versa
        if ($actual === 'ADMIN' && $requested === 'USER') {
            return false;
        }
        
        if ($actual === 'USER' && $requested === 'ADMIN') {
            return false;
        }

        return $actual === $requested;
    }

    // =============================
    // CURRENT USER
    // =============================

    public function getCurrentUser(): array|false
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        return $this->authRepo->findUserById(
            (int) $_SESSION['user_id']
        );
    }

    // =============================
    // CSRF
    // =============================

    public function getCsrfToken(): string
    {
        $this->startSession();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION['csrf_token'];
    }

    public function verifyCsrfToken(string $token): bool
    {
        $this->startSession();

        return $token !== ''
            && isset($_SESSION['csrf_token'])
            && hash_equals(
                $_SESSION['csrf_token'],
                $token
            );
    }

    // =============================
    // LOGOUT
    // =============================

    public function logout(): void
    {
        $this->startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }

        session_destroy();
    }
}
