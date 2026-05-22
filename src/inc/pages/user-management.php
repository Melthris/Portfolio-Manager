<?php
/**
 * User Management page.
 *
 * The primary owner uses this page to create additional users, assign module
 * permissions, and reset passwords while keeping the same admin layout used by
 * the rest of Portfolio Manager. The create and edit panels intentionally work
 * like Manage Projects: create is shown by default, while edit is only shown
 * after a user is selected from the sidebar.
 */

declare(strict_types=1);

pmRequirePermission('can_manage_users');

$pdo = pmDb();
$users = $pdo->query('SELECT * FROM users ORDER BY is_primary_owner DESC, username ASC')->fetchAll();
$permissions = pmPermissionKeys();
$selectedUserId = (int) ($_GET['edit_user'] ?? 0);
$selectedUser = $selectedUserId > 0 ? pmFindUserById($selectedUserId) : null;
$isSelectedPrimaryOwner = $selectedUser !== null && (int) ($selectedUser['is_primary_owner'] ?? 0) === 1;

$statusMessages = [
    'created' => ['success', 'User created.'],
    'password-reset' => ['success', 'Password reset.'],
    'permissions-saved' => ['success', 'Permissions saved.'],
    'profile-saved' => ['success', 'User profile saved.'],
    'invalid' => ['error', 'Your session expired. Please try again.'],
    'missing' => ['error', 'Required user details are missing.'],
    'bad-user' => ['error', 'Username and a temporary password of at least 12 characters are required.'],
    'bad-reset' => ['error', 'Password reset failed. Temporary passwords must be at least 12 characters.'],
    'locked-owner' => ['error', 'The primary owner account can only have profile details edited.'],
];
$status = pmString($_GET['status'] ?? '');
$flash = $statusMessages[$status] ?? null;
?>

<section class="manage-projects-page user-management-page">
    <div class="manage-projects-header">
        <div>
            <p class="admin-kicker">Admin Portal</p>
            <h1 class="admin-page-title">User Management</h1>
            <p class="admin-page-subtitle">
                Create additional users, choose page access, and reset passwords without affecting the primary owner account.
            </p>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <p class="contact-flash contact-flash-<?= pmEscape($flash[0]) ?>" role="alert"><?= pmEscape($flash[1]) ?></p>
    <?php endif; ?>

    <div class="manage-projects-layout user-management-layout">
        <aside class="project-admin-sidebar user-admin-sidebar">
            <div class="admin-panel-heading">
                <h2>Existing Users</h2>
                <p>Select a user to edit their public display name, recovery email, permissions, or password.</p>
            </div>

            <div class="project-admin-list user-admin-list">
                <?php foreach ($users as $user): ?>
                    <?php
                    $userId = (int) $user['id'];
                    $isPrimaryOwner = (int) $user['is_primary_owner'] === 1;
                    $isSelected = $selectedUser !== null && $userId === (int) $selectedUser['id'];
                    ?>
                    <article class="user-list-card<?= $isSelected ? ' active' : '' ?><?= $isPrimaryOwner ? ' protected' : '' ?>">
                        <a class="user-list-card-link" href="index.php?page=user-management&amp;edit_user=<?= $userId ?>" <?= $isPrimaryOwner ? 'aria-label="Edit primary owner profile"' : '' ?>>
                            <strong><?= pmEscape((string) $user['username']) ?><?= $isPrimaryOwner ? ' · Primary owner' : '' ?></strong>
                            <span><?= pmEscape(pmUserDisplayName($user)) ?></span>
                            <?php if (!empty($user['email'])): ?><small><?= pmEscape((string) $user['email']) ?></small><?php endif; ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="project-admin-workspace user-admin-workspace">
            <?php if ($selectedUser === null): ?>
                <article class="admin-card user-create-card">
                    <div class="admin-card-header">
                        <div>
                            <p class="admin-kicker">New User</p>
                            <h2>Create User</h2>
                        </div>
                    </div>

                    <form method="post" action="src/inc/user-handler.php">
                        <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                        <input type="hidden" name="action" value="create">

                        <div class="admin-form-grid">
                            <label class="admin-field"><span>Username</span><input class="textbox" name="username" required></label>
                            <label class="admin-field"><span>Display name</span><input class="textbox" name="display_name"></label>
                            <label class="admin-field"><span>Email</span><input class="textbox" name="email" type="email"></label>
                            <label class="admin-field"><span>Recovery email</span><input class="textbox" name="recovery_email" type="email"></label>
                            <label class="admin-field admin-field-wide"><span>Temporary password</span><input class="textbox" name="password" type="password" minlength="12" required></label>
                        </div>

                        <div class="admin-field admin-field-wide permission-selector-block">
                            <span>Permissions</span>
                            <div class="permission-grid">
                                <?php foreach ($permissions as $permission): ?>
                                    <label class="permission-option">
                                        <input type="checkbox" name="permissions[]" value="<?= pmEscape($permission) ?>">
                                        <span><?= pmEscape($permission) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="admin-actions">
                            <button type="submit">Create user</button>
                        </div>
                    </form>
                </article>
            <?php else: ?>
                <article class="admin-card user-edit-card">
                    <div class="admin-card-header">
                        <div>
                            <p class="admin-kicker"><?= $isSelectedPrimaryOwner ? 'Primary Owner' : 'Existing User' ?></p>
                            <h2><?= $isSelectedPrimaryOwner ? 'Edit Primary Owner Profile' : 'Edit Existing User' ?></h2>
                            <p class="admin-card-subtitle">
                                <?php if ($isSelectedPrimaryOwner): ?>
                                    Update the primary owner display name used for blog post author attribution. Permissions and ownership protections remain locked.
                                <?php else: ?>
                                    Editing <?= pmEscape((string) $selectedUser['username']) ?>. Use the sidebar or cancel to return to user creation.
                                <?php endif; ?>
                            </p>
                        </div>
                        <a class="admin-secondary-link" href="index.php?page=user-management">Create New User</a>
                    </div>

                    <div class="selected-user-summary">
                        <strong><?= pmEscape(pmUserDisplayName($selectedUser)) ?></strong>
                        <?php if (!empty($selectedUser['email'])): ?><span><?= pmEscape((string) $selectedUser['email']) ?></span><?php endif; ?>
                        <?php if (!empty($selectedUser['recovery_email'])): ?><small>Recovery: <?= pmEscape((string) $selectedUser['recovery_email']) ?></small><?php endif; ?>
                    </div>

                    <form method="post" action="src/inc/user-handler.php" class="user-profile-form">
                        <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                        <input type="hidden" name="action" value="profile">
                        <input type="hidden" name="user_id" value="<?= (int) $selectedUser['id'] ?>">

                        <div class="admin-form-grid">
                            <label class="admin-field">
                                <span>Display name</span>
                                <input class="textbox" name="display_name" value="<?= pmEscape(pmUserDisplayName($selectedUser)) ?>" required>
                            </label>
                            <label class="admin-field">
                                <span>Email</span>
                                <input class="textbox" name="email" type="email" value="<?= pmEscape((string) ($selectedUser['email'] ?? '')) ?>">
                            </label>
                            <label class="admin-field admin-field-wide">
                                <span>Recovery email</span>
                                <input class="textbox" name="recovery_email" type="email" value="<?= pmEscape((string) ($selectedUser['recovery_email'] ?? '')) ?>">
                            </label>
                        </div>

                        <div class="admin-actions">
                            <button type="submit">Save profile</button>
                        </div>
                    </form>

                    <?php if (!$isSelectedPrimaryOwner): ?>
                        <form method="post" action="src/inc/user-handler.php" class="user-permission-form">
                            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                            <input type="hidden" name="action" value="permissions">
                            <input type="hidden" name="user_id" value="<?= (int) $selectedUser['id'] ?>">

                            <div class="admin-field admin-field-wide permission-selector-block">
                                <span>Permissions</span>
                                <div class="permission-grid">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php
                                        $stmt = $pdo->prepare('SELECT can_access FROM user_permissions WHERE user_id = :id AND permission_key = :permission');
                                        $stmt->execute([':id' => (int) $selectedUser['id'], ':permission' => $permission]);
                                        $has = (int) $stmt->fetchColumn() === 1;
                                        ?>
                                        <label class="permission-option">
                                            <input type="checkbox" name="permissions[]" value="<?= pmEscape($permission) ?>" <?= $has ? 'checked' : '' ?>>
                                            <span><?= pmEscape($permission) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="admin-actions">
                                <button type="submit">Save permissions</button>
                            </div>
                        </form>

                        <form method="post" action="src/inc/user-handler.php" class="user-reset-form">
                            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                            <input type="hidden" name="action" value="reset-password">
                            <input type="hidden" name="user_id" value="<?= (int) $selectedUser['id'] ?>">
                            <label class="admin-field admin-field-wide">
                                <span>New temporary password</span>
                                <input class="textbox" name="password" type="password" minlength="12" required>
                            </label>
                            <div class="admin-actions">
                                <button class="blog-delete-button cv-publish-button" type="submit">Reset password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
        </section>
    </div>
</section>
