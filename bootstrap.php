<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

// ── Autoloader ───────────────────────────────────────────────
spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/src/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ── PDO singleton ────────────────────────────────────────────
function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

// ── Session — compatible HTTP local (XAMPP) ──────────────────
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');   // Strict bloque sur localhost
        ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,    // false = marche en HTTP sur XAMPP
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ── JSON helpers ─────────────────────────────────────────────
function jsonSuccess(mixed $data = null, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: http://localhost');
    header('Access-Control-Allow-Credentials: true');
    echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $code = 400): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: http://localhost');
    header('Access-Control-Allow-Credentials: true');
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth guard ───────────────────────────────────────────────
function requireAuth(): int
{
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        jsonError('Unauthorized. Please log in.', 401);
    }
    return (int) $_SESSION['user_id'];
}

// ── Input helper ─────────────────────────────────────────────
function input(string $key, string $default = ''): string
{
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $value = $body[$key] ?? $_POST[$key] ?? $_GET[$key] ?? $default;
    return trim((string) $value);
}

// ── Client IP ────────────────────────────────────────────────
function clientIp(): string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? 'unknown';
}