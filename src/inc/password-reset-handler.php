<?php
/**
 * Password reset request and completion handler.
 *
 * The staged build stores reset tokens but does not send SMTP mail yet. When
 * mail mode is disabled, the reset link is shown in-session for local testing.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmSendNoCacheHeaders();
pmStartSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=password-reset&status=invalid');
}

$action = pmString($_POST['action'] ?? 'request');

if ($action === 'request') {
    $login = pmString($_POST['login'] ?? '');
    $user = $login !== '' ? pmFindUserByLogin($login) : null;

    unset($_SESSION['pm_password_reset_test_link']);

    if ($user !== null && trim((string) $user['recovery_email']) !== '') {
        $selector = bin2hex(random_bytes(9));
        $token = bin2hex(random_bytes(32));
        $stmt = pmDb()->prepare(<<<'SQL'
            INSERT INTO password_reset_tokens (user_id, selector, token_hash, expires_at, request_ip)
            VALUES (:user_id, :selector, :token_hash, datetime('now', '+1 hour'), :request_ip)
        SQL);
        $stmt->execute([
            ':user_id' => (int) $user['id'],
            ':selector' => $selector,
            ':token_hash' => hash('sha256', $token),
            ':request_ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
        ]);

        $link = 'index.php?page=password-reset&selector=' . rawurlencode($selector) . '&token=' . rawurlencode($token);
        $_SESSION['pm_password_reset_test_link'] = $link;
    }

    pmRedirect('../../index.php?page=password-reset&status=requested');
}

if ($action === 'complete') {
    $selector = pmString($_POST['selector'] ?? '');
    $token = pmString($_POST['token'] ?? '');
    $password = pmString($_POST['password'] ?? '');
    $confirm = pmString($_POST['confirm_password'] ?? '');

    if ($selector === '' || $token === '' || strlen($password) < 12 || $password !== $confirm) {
        pmRedirect('../../index.php?page=password-reset&status=bad-password');
    }

    $stmt = pmDb()->prepare(<<<'SQL'
        SELECT * FROM password_reset_tokens
        WHERE selector = :selector
          AND used_at IS NULL
          AND datetime(expires_at) > datetime('now')
        LIMIT 1
    SQL);
    $stmt->execute([':selector' => $selector]);
    $row = $stmt->fetch();

    if (!is_array($row) || !hash_equals((string) $row['token_hash'], hash('sha256', $token))) {
        pmRedirect('../../index.php?page=password-reset&status=invalid-token');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $update = pmDb()->prepare('UPDATE users SET password_hash = :hash, must_change_password = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $update->execute([':hash' => $hash, ':id' => (int) $row['user_id']]);
    pmDb()->prepare('UPDATE password_reset_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id' => (int) $row['id']]);
    pmDb()->prepare('DELETE FROM remember_tokens WHERE user_id = :id')->execute([':id' => (int) $row['user_id']]);

    pmRedirect('../../index.php?page=adminlogonportal&reset=success');
}

pmRedirect('../../index.php?page=password-reset&status=unknown');
