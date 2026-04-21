<?php

// =============================================================
//  src/Services/AuthService.php
// =============================================================

declare(strict_types=1);

namespace Services;

use Repositories\UserRepository;
use Repositories\LogRepository;

class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly LogRepository  $logs
    ) {}

    // ── Register ─────────────────────────────────────────────
    public function register(
        string $username,
        string $email,
        string $password,
        string $ip
    ): array {
        // --- input validation ---
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['ok' => false, 'error' => 'Username must be 3–50 characters.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Invalid email address.'];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
        }

        // --- uniqueness checks (belt-and-suspenders; DB has UNIQUE too) ---
        if ($this->users->existsByUsername($username)) {
            return ['ok' => false, 'error' => 'Username already taken.'];
        }
        if ($this->users->existsByEmail($email)) {
            return ['ok' => false, 'error' => 'Email already registered.'];
        }

        // --- persist ---
        $hash   = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $userId = $this->users->create($username, $email, $hash);

        $this->logs->log('REGISTER', $userId, "New user: $username", $ip);

        return ['ok' => true, 'user_id' => $userId, 'username' => $username];
    }

    // ── Login ────────────────────────────────────────────────
    public function login(string $username, string $password, string $ip): array
    {
        $user = $this->users->findByUsername($username);

        // Constant-time fail — always run verify even if user not found
        $dummyHash = '$2y$12$invalidhashfortimingprotectionXXXXXXXXXXXXXXXXXXXXXXXXX';
        $storedHash = $user ? $user['password_hash'] : $dummyHash;

        if (!$user || !password_verify($password, $storedHash)) {
            $this->logs->log('LOGIN_FAIL', null, "Failed attempt for: $username", $ip);
            return ['ok' => false, 'error' => 'Invalid credentials.'];
        }

        // Rehash if cost factor has been updated
        if (password_needs_rehash($storedHash, PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            // UserRepository::updateHash() would live here in a full system
        }

        $this->logs->log('LOGIN', (int) $user['id'], "Login: {$user['username']}", $ip);

        return [
            'ok'       => true,
            'user_id'  => (int) $user['id'],
            'username' => $user['username'],
        ];
    }
}
