<?php
/**
 * Password reset request/completion page.
 */

declare(strict_types=1);

pmStartSession();
$selector = pmString($_GET['selector'] ?? '');
$token = pmString($_GET['token'] ?? '');
$status = pmString($_GET['status'] ?? '');
?>
<section class="auth-card">
    <h1>Password Reset</h1>
    <?php if ($status !== ''): ?><p class="alert">Status: <?= pmEscape($status) ?></p><?php endif; ?>
    <?php if (!empty($_SESSION['pm_password_reset_test_link'])): ?>
        <p class="alert alert-success">Mailer is disabled. Local test reset link: <a href="<?= pmEscape((string) $_SESSION['pm_password_reset_test_link']) ?>">open reset link</a></p>
    <?php endif; ?>

    <?php if ($selector !== '' && $token !== ''): ?>
        <form method="post" action="src/inc/password-reset-handler.php" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
            <input type="hidden" name="action" value="complete">
            <input type="hidden" name="selector" value="<?= pmEscape($selector) ?>">
            <input type="hidden" name="token" value="<?= pmEscape($token) ?>">
            <label>New password <input name="password" type="password" minlength="12" required></label>
            <label>Confirm password <input name="confirm_password" type="password" minlength="12" required></label>
            <button class="button" type="submit">Update password</button>
        </form>
    <?php else: ?>
        <form method="post" action="src/inc/password-reset-handler.php" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
            <input type="hidden" name="action" value="request">
            <label>Username or email <input name="login" required></label>
            <button class="button" type="submit">Request reset</button>
        </form>
    <?php endif; ?>
</section>
