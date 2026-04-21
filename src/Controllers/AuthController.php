<?php

// =============================================================
//  src/Controllers/AuthController.php
// =============================================================

declare(strict_types=1);

namespace Controllers;

use Services\AuthService;
use Repositories\UserRepository;
use Repositories\LogRepository;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $pdo = getPDO();
        $this->authService = new AuthService(
            new UserRepository($pdo),
            new LogRepository($pdo)
        );
    }

    // POST /api/auth/register
    public function register(): void
    {
        $username = input('username');
        $email    = input('email');
        $password = input('password');

        if ($username === '' || $email === '' || $password === '') {
            jsonError('username, email, and password are required.');
        }

        $result = $this->authService->register($username, $email, $password, clientIp());

        if (!$result['ok']) {
            jsonError($result['error'], 422);
        }

        jsonSuccess(['username' => $result['username']], 201);
    }

    // POST /api/auth/login
    public function login(): void
    {
        startSecureSession();

        $username = input('username');
        $password = input('password');

        if ($username === '' || $password === '') {
            jsonError('username and password are required.');
        }

        $result = $this->authService->login($username, $password, clientIp());

        if (!$result['ok']) {
            jsonError($result['error'], 401);
        }

        // Regenerate session ID on privilege escalation
        session_regenerate_id(true);
        $_SESSION['user_id']  = $result['user_id'];
        $_SESSION['username'] = $result['username'];

        jsonSuccess(['username' => $result['username'], 'user_id' => $result['user_id']]);
    }

    // POST /api/auth/logout
    public function logout(): void
    {
        startSecureSession();
        $_SESSION = [];
        session_destroy();
        jsonSuccess(['message' => 'Logged out successfully.']);
    }
}
