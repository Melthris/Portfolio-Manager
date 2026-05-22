<?php
/**
 * Shared Portfolio Manager header.
 *
 * The header keeps the same class structure used by the newer layout
 * system so the CSS ports cleanly, but all labels and function calls are
 * generic Portfolio Manager implementations.
 */

declare(strict_types=1);

$isLoggedIn = pmIsLoggedIn();
$displayName = $isLoggedIn ? pmLoggedInDisplayName() : null;
?>
<header class="banner">
    <a class="top-float-name" href="index.php?page=home" aria-label="Portfolio Manager home">
        <span>Portfolio Manager <em class="version-mark">v<?= pmEscape(SITE_VERSION) ?></em></span>
    </a>

    <nav class="top-float" aria-label="Main navigation">
        <?php $links = pmPublicNavigationLinks(); ?>
        <?php foreach ($links as $index => $link): ?>
            <?php if ($index > 0): ?><span>|</span><?php endif; ?>
            <a class="header-links" href="index.php?page=<?= pmEscape($link['page']) ?>"><?= pmEscape($link['label']) ?></a>
        <?php endforeach; ?>

        <?php if ($isLoggedIn): ?>
            <?php $adminLinks = pmAdminNavigationLinks(); ?>
            <?php foreach ($adminLinks as $link): ?>
                <span>|</span>
                <a class="header-links" href="index.php?page=<?= pmEscape($link['page']) ?>"><?= pmEscape($link['label']) ?></a>
            <?php endforeach; ?>
        <?php else: ?>
            <span>|</span>
            <a class="header-links" href="index.php?page=adminlogonportal">Login</a>
        <?php endif; ?>
    </nav>

    <?php if ($displayName !== null): ?>
        <div class="greeting">
            <p>Hello, <?= pmEscape($displayName) ?>!</p>
        </div>
    <?php endif; ?>
</header>
