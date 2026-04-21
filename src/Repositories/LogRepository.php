<?php

// =============================================================
//  src/Repositories/LogRepository.php
// =============================================================

declare(strict_types=1);

namespace Repositories;

use PDO;

class LogRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function log(
        string $action,
        ?int   $userId = null,
        string $detail = '',
        string $ip     = ''
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, detail, ip_address)
             VALUES (:user_id, :action, :detail, :ip_address)'
        );
        $stmt->execute([
            ':user_id'    => $userId,
            ':action'     => strtoupper(substr($action, 0, 50)),
            ':detail'     => substr($detail, 0, 65535),
            ':ip_address' => substr($ip, 0, 45),
        ]);
    }

    // ── Fetch logs for audit display ─────────────────────────
    public function recent(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.action, l.detail, l.ip_address, l.created_at, u.username
               FROM audit_logs l
          LEFT JOIN users u ON u.id = l.user_id
              ORDER BY l.id DESC
              LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
