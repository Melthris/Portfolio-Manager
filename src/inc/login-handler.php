<?php
/**
 * Login form handler.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmSendNoCacheHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pmRedirect('../../index.php?page=adminlogonportal');
}

if (!pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=adminlogonportal&error=csrf');
}

$login = pmString($_POST['username'] ?? '');
$password = pmString($_POST['password'] ?? '');
$rememberMe = isset($_POST['remember_me']);
$rememberUsername = isset($_POST['remember_username']);

pmStoreRememberedUsername($login, $rememberUsername);

if ($login === '' || $password === '') {
    pmRedirect('../../index.php?page=adminlogonportal&error=missing');
}

if (!pmLogin($login, $password, $rememberMe)) {
    pmRedirect('../../index.php?page=adminlogonportal&error=invalid');
}

pmRedirect('../../index.php?page=manage-project');
