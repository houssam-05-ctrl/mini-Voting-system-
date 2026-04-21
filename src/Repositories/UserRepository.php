<?php

// =============================================================
//  src/Repositories/UserRepository.php
// =============================================================

declare(strict_types=1);

namespace Repositories;

use PDO;

class UserRepository
{
    public function __construct(private readonly PDO $pdo) {}

    // ── Find by username (login) ─────────────────────────────
    public function findByUsername(string $username): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, password_hash
               FROM users
              WHERE username = :username
              LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    // ── Find by id ───────────────────────────────────────────
    public function findById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, created_at
               FROM users
              WHERE id = :id
              LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── Existence checks ─────────────────────────────────────
    public function existsByUsername(string $username): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        return (bool) $stmt->fetchColumn();
    }

    public function existsByEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        return (bool) $stmt->fetchColumn();
    }

    // ── Insert new user ──────────────────────────────────────
    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash)
             VALUES (:username, :email, :password_hash)'
        );
        $stmt->execute([
            ':username'      => $username,
            ':email'         => $email,
            ':password_hash' => $passwordHash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
