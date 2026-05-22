<?php
/**
 * Shared Portfolio Manager footer.
 */

declare(strict_types=1);

$footerSocialLinks = function_exists('pmFooterSocialLinks') ? pmFooterSocialLinks() : [];
?>
<div class="footer">
    <?php if ($footerSocialLinks !== []): ?>
        <nav class="footer-social-links" aria-label="Footer social media links">
            <?= pmRenderSocialIconLinks($footerSocialLinks, 'footer-social-link') ?>
        </nav>
    <?php endif; ?>

    <h6> | Powered by Portfolio Manager (v<?= pmEscape(SITE_VERSION) ?>)</h6>
</div>
</body>
</html>
