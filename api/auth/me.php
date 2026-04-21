<?php
require_once __DIR__ . '/../../bootstrap.php';
startSecureSession();

if (!empty($_SESSION['user_id'])) {
    jsonSuccess([
        'user_id'  => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
    ]);
} else {
    jsonError('Not authenticated', 401);
}
