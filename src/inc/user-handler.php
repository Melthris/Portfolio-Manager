<?php
/**
 * User Management handler.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmRequirePermission('can_manage_users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=user-management&status=invalid');
}

$action = pmString($_POST['action'] ?? 'create');

if ($action === 'create') {
    $username = pmString($_POST['username'] ?? '');
    $email = pmString($_POST['email'] ?? '');
    $recoveryEmail = pmString($_POST['recovery_email'] ?? '');
    $displayName = pmString($_POST['display_name'] ?? $username);
    $password = pmString($_POST['password'] ?? '');

    if ($username === '' || strlen($password) < 12) {
        pmRedirect('../../index.php?page=user-management&status=bad-user');
    }

    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO users (username, email, recovery_email, display_name, password_hash, role, is_active, must_change_password)
        VALUES (:username, :email, :recovery_email, :display_name, :password_hash, 'admin', 1, 1)
    SQL);
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':recovery_email' => $recoveryEmail,
        ':display_name' => $displayName,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $newUserId = (int) pmDb()->lastInsertId();
    pmSetUserPermissions(pmDb(), $newUserId, pmStringList($_POST['permissions'] ?? []));
    pmRedirect('../../index.php?page=user-management&status=created');
}


if ($action === 'profile') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $user = pmFindUserById($userId);
    $displayName = pmString($_POST['display_name'] ?? '');
    $email = pmString($_POST['email'] ?? '');
    $recoveryEmail = pmString($_POST['recovery_email'] ?? '');

    if ($user === null || $displayName === '') {
        pmRedirect('../../index.php?page=user-management&status=missing');
    }

    /**
     * Update the selected user's public profile information.
     *
     * The primary owner is allowed through this action because the display name
     * is used for public blog author attribution. This action deliberately does
     * not touch primary-owner permissions, role, ownership flags, or password
     * state, so the protected account cannot be weakened from this form.
     */
    $stmt = pmDb()->prepare(<<<'SQL'
        UPDATE users
        SET display_name = :display_name,
            email = :email,
            recovery_email = :recovery_email,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    SQL);
    $stmt->execute([
        ':display_name' => $displayName,
        ':email' => $email,
        ':recovery_email' => $recoveryEmail,
        ':id' => $userId,
    ]);

    /**
     * Refresh the active session display name if the logged-in user changed
     * their own profile. This keeps the header greeting correct immediately
     * after saving without requiring a logout/login cycle.
     */
    if (pmCurrentUserId() === $userId) {
        $_SESSION['pm_display_name'] = $displayName;
    }

    pmRedirect('../../index.php?page=user-management&edit_user=' . $userId . '&status=profile-saved');
}

if ($action === 'reset-password') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $password = pmString($_POST['password'] ?? '');
    $user = pmFindUserById($userId);

    if ($userId <= 0 || strlen($password) < 12 || $user === null || (int) $user['is_primary_owner'] === 1) {
        pmRedirect('../../index.php?page=user-management&status=bad-reset');
    }

    $stmt = pmDb()->prepare('UPDATE users SET password_hash = :hash, must_change_password = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $userId]);
    pmDb()->prepare('DELETE FROM remember_tokens WHERE user_id = :id')->execute([':id' => $userId]);
    pmRedirect('../../index.php?page=user-management&edit_user=' . $userId . '&status=password-reset');
}

if ($action === 'permissions') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $user = pmFindUserById($userId);

    if ($user === null || (int) $user['is_primary_owner'] === 1) {
        pmRedirect('../../index.php?page=user-management&status=locked-owner');
    }

    pmSetUserPermissions(pmDb(), $userId, pmStringList($_POST['permissions'] ?? []));
    pmRedirect('../../index.php?page=user-management&edit_user=' . $userId . '&status=permissions-saved');
}

pmRedirect('../../index.php?page=user-management&status=unknown');
