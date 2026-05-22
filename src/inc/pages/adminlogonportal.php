<?php
/**
 * Admin login page.
 *
 * The form keeps the original Login Portal/project-box2 styling while using
 * the newer SQLite authentication, CSRF protection, and remember controls.
 */

declare(strict_types=1);

pmSendNoCacheHeaders();
$error = pmString($_GET['error'] ?? '');
$reset = pmString($_GET['reset'] ?? '');
?>
<form action="src/inc/login-handler.php" method="post" name="Login_Form">
    <h1>Login Portal</h1>
    <div class="container2">
        <div class="project-box2">
            <div class="project-container"></div>
            <div class="login-details">
                <?php if ($error !== ''): ?><h3>Login issue: <?= pmEscape($error) ?></h3><?php endif; ?>
                <?php if ($reset === 'success'): ?><h3>Password updated. You can now log in.</h3><?php endif; ?>

                <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">

                <h4>User or Email:</h4>
                <div class="user-text"><input name="username" class="textbox" type="text" value="<?= pmEscape(pmRememberedUsername()) ?>" required></div>

                <h4>Password:</h4>
                <div class="user-text"><input name="password" class="textbox" type="password" required></div>

                <label class="checkbox-row"><input type="checkbox" name="remember_username" <?= pmRememberedUsername() !== '' ? 'checked' : '' ?>> Remember username</label>
                <label class="checkbox-row"><input type="checkbox" name="remember_me"> Keep me logged in</label>

                <input name="Submit" type="submit" value="Login" class="button">
                <h4><a class="header-links" href="index.php?page=password-reset">Forgot your password?</a></h4>
            </div>
        </div>
    </div>
</form>
