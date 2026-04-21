<?php

// =============================================================
//  src/Controllers/VoteController.php
// =============================================================

declare(strict_types=1);

namespace Controllers;

use Services\VoteService;
use Repositories\VoteRepository;
use Repositories\LogRepository;

class VoteController
{
    private VoteService    $voteService;
    private VoteRepository $voteRepo;

    public function __construct()
    {
        $pdo = getPDO();
        $this->voteRepo    = new VoteRepository($pdo);
        $this->voteService = new VoteService($pdo, $this->voteRepo, new LogRepository($pdo));
    }

    // POST /api/vote/submit  (requires auth)
    public function submit(): void
    {
        $userId = requireAuth();
        $choice = input('choice');

        if ($choice === '') {
            jsonError('"choice" is required. Valid: ' . implode(', ', VoteService::validChoices()));
        }

        $result = $this->voteService->submit($userId, $choice, clientIp());

        if (!$result['ok']) {
            $code = str_contains($result['error'], 'already voted') ? 409 : 422;
            jsonError($result['error'], $code);
        }

        jsonSuccess([
            'message' => 'Vote recorded successfully.',
            'vote_id' => $result['vote_id'],
            'hash'    => $result['hash'],
        ], 201);
    }

    // GET /api/vote/results  (public)
    public function results(): void
    {
        $breakdown = $this->voteRepo->countByChoice();
        $total     = $this->voteRepo->totalCount();

        jsonSuccess([
            'total'     => $total,
            'breakdown' => $breakdown,
        ]);
    }
}
