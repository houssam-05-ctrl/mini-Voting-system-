<?php

// =============================================================
//  src/Services/VoteService.php
//  Core business logic:
//    • One-vote-per-user validation
//    • SHA-256 chained hash computation
//    • Atomic insertion inside an InnoDB transaction
// =============================================================

declare(strict_types=1);

namespace Services;

use PDO;
use Repositories\VoteRepository;
use Repositories\LogRepository;

class VoteService
{
    // Allowed ballot choices (extend as needed)
    private const VALID_CHOICES = ['candidate_a', 'candidate_b', 'candidate_c'];

    public function __construct(
        private readonly PDO            $pdo,
        private readonly VoteRepository $votes,
        private readonly LogRepository  $logs
    ) {}

    // ── Submit a vote ────────────────────────────────────────
    public function submit(int $userId, string $choice, string $ip): array
    {
        $choice = strtolower(trim($choice));

        // --- validate choice ---
        if (!in_array($choice, self::VALID_CHOICES, true)) {
            return ['ok' => false, 'error' => 'Invalid choice.'];
        }

        // ── Begin exclusive transaction ──────────────────────
        // SELECT FOR UPDATE locks the last row so two concurrent
        // votes cannot race and pick the same previous_hash.
        $this->pdo->beginTransaction();

        try {
            // --- duplicate-vote guard (DB UNIQUE will also catch it) ---
            if ($this->votes->hasVoted($userId)) {
                $this->pdo->rollBack();
                $this->logs->log('DUPLICATE_VOTE', $userId, "Choice: $choice", $ip);
                return ['ok' => false, 'error' => 'You have already voted.'];
            }

            // --- build hash chain ---
            $previousHash = $this->votes->getLastHash();      // tail of chain
            $timestamp    = date('Y-m-d H:i:s');
            $hash         = $this->computeHash($userId, $choice, $timestamp, $previousHash);

            // --- persist ---
            $voteId = $this->votes->insert($userId, $choice, $hash, $previousHash);

            $this->pdo->commit();

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            // Log internally but surface a generic error to the caller
            error_log('[VoteService] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Vote could not be recorded. Please retry.'];
        }

        $this->logs->log('VOTE', $userId, "Vote #$voteId choice=$choice hash=$hash", $ip);

        return [
            'ok'      => true,
            'vote_id' => $voteId,
            'hash'    => $hash,
        ];
    }

    // ── Compute SHA-256 chain hash ───────────────────────────
    //   hash = SHA256( user_id | choice | timestamp | previous_hash )
    public static function computeHash(
        int    $userId,
        string $choice,
        string $timestamp,
        string $previousHash
    ): string {
        $payload = implode('|', [$userId, $choice, $timestamp, $previousHash]);
        return hash('sha256', $payload);
    }

    // ── Valid choices accessor (used by controllers/views) ───
    public static function validChoices(): array
    {
        return self::VALID_CHOICES;
    }
}
