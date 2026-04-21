<?php

// =============================================================
//  src/Controllers/AuditController.php
// =============================================================

declare(strict_types=1);

namespace Controllers;

use Services\AuditService;
use Repositories\VoteRepository;
use Repositories\LogRepository;

class AuditController
{
    private AuditService $auditService;
    private LogRepository $logRepo;

    public function __construct()
    {
        $pdo = getPDO();
        $this->auditService = new AuditService(new VoteRepository($pdo));
        $this->logRepo      = new LogRepository($pdo);
    }

    // GET /api/audit/chain  (public — verification is always transparent)
    public function chain(): void
    {
        $report = $this->auditService->verifyChain();
        $code   = $report['valid'] ? 200 : 409;   // 409 Conflict = tamper detected
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'data' => $report], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // GET /api/audit/logs  (requires auth — restrict to admin in production)
    public function logs(): void
    {
        requireAuth();
        $limit = min((int) input('limit', '100'), 500);
        jsonSuccess($this->logRepo->recent($limit));
    }
}
