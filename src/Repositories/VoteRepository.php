<?php

// =============================================================
//  src/Repositories/VoteRepository.php
// =============================================================

declare(strict_types=1);

namespace Repositories;

use PDO;

class VoteRepository
{
    public function __construct(private readonly PDO $pdo) {}

    // ── Retrieve the hash of the most-recent vote (tail) ────
    //    Returns the genesis sentinel when no votes exist yet.
    public function getLastHash(): string
    {
        $stmt = $this->pdo->query(
            'SELECT hash
               FROM votes
              ORDER BY id DESC
              LIMIT 1'
        );
        return $stmt->fetchColumn() ?: CHAIN_GENESIS_HASH;
    }

    // ── Check whether a user has already voted ───────────────
    public function hasVoted(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM votes WHERE user_id = :user_id LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    // ── Insert a vote (called inside a transaction) ──────────
    public function insert(int $userId, string $choice, string $hash, string $previousHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO votes (user_id, choice, hash, previous_hash)
             VALUES (:user_id, :choice, :hash, :previous_hash)'
        );
        $stmt->execute([
            ':user_id'       => $userId,
            ':choice'        => $choice,
            ':hash'          => $hash,
            ':previous_hash' => $previousHash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    // ── Results aggregation ──────────────────────────────────
    public function countByChoice(): array
    {
        $stmt = $this->pdo->query(
            'SELECT choice, COUNT(*) AS total
               FROM votes
              GROUP BY choice
              ORDER BY total DESC'
        );
        return $stmt->fetchAll();
    }

    // ── Retrieve the full chain ordered for verification ────
    public function getFullChain(): array
    {
        $stmt = $this->pdo->query(
            'SELECT v.id, v.user_id, v.choice, v.hash, v.previous_hash,
                    v.voted_at, u.username
               FROM votes v
               JOIN users u ON u.id = v.user_id
              ORDER BY v.id ASC'
        );
        return $stmt->fetchAll();
    }

    // ── Total votes ──────────────────────────────────────────
    public function totalCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn();
    }
}
