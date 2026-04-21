<?php

// =============================================================
//  src/Services/AuditService.php
//  Walks the entire vote chain and recomputes every hash.
//  Reports the first tampered record (if any).
// =============================================================

declare(strict_types=1);

namespace Services;

use Repositories\VoteRepository;
use Services\VoteService;

class AuditService
{
    public function __construct(
        private readonly VoteRepository $votes
    ) {}

    // ── Verify the full chain ────────────────────────────────
    //   Returns:
    //     valid        bool   — true only if every link is intact
    //     total        int    — total votes inspected
    //     tampered_at  ?int   — vote.id of first broken link, or null
    //     detail       string — human-readable summary
    public function verifyChain(): array
    {
        $chain = $this->votes->getFullChain();

        if (empty($chain)) {
            return [
                'valid'       => true,
                'total'       => 0,
                'tampered_at' => null,
                'detail'      => 'Chain is empty — no votes yet.',
                'votes'       => [],
            ];
        }

        $expectedPrevious = CHAIN_GENESIS_HASH;
        $tamperedAt       = null;
        $annotated        = [];

        foreach ($chain as $vote) {
            // Recompute the hash from stored fields
            $recomputed = VoteService::computeHash(
                (int) $vote['user_id'],
                $vote['choice'],
                $vote['voted_at'],
                $vote['previous_hash']
            );

            $linkValid    = hash_equals($recomputed, $vote['hash']);
            $prevValid    = hash_equals($expectedPrevious, $vote['previous_hash']);
            $entryTampered = !$linkValid || !$prevValid;

            if ($entryTampered && $tamperedAt === null) {
                $tamperedAt = (int) $vote['id'];
            }

            $annotated[] = [
                'id'           => (int) $vote['id'],
                'username'     => $vote['username'],
                'choice'       => $vote['choice'],
                'voted_at'     => $vote['voted_at'],
                'hash'         => $vote['hash'],
                'previous_hash'=> $vote['previous_hash'],
                'hash_valid'   => $linkValid,
                'chain_valid'  => $prevValid,
            ];

            $expectedPrevious = $vote['hash'];
        }

        $valid = $tamperedAt === null;

        return [
            'valid'       => $valid,
            'total'       => count($chain),
            'tampered_at' => $tamperedAt,
            'detail'      => $valid
                ? "All {$annotated[count($annotated)-1]['id']} vote(s) verified — chain intact."
                : "⚠ Tamper detected at vote ID $tamperedAt.",
            'votes'       => $annotated,
        ];
    }
}
