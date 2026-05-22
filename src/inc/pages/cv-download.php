<?php
/**
 * Public CV preview and download/print page.
 *
 * This page uses the same full-width public page shell as Blog and Portfolio so
 * the Current CV route does not collapse into the generic scaffold layout.
 */

declare(strict_types=1);

$build = pmGetPublicCvBuild();
?>

<section class="cv-download-page">
    <div class="blog-header cv-download-header">
        <p class="admin-kicker">Current CV</p>
        <h1 class="blog-page-title">Download CV</h1>
        <p class="blog-page-subtitle">
            The publicly selected CV build is shown below in an ATS-friendly format and can be printed or saved as PDF.
        </p>
    </div>

    <?php if ($build === null): ?>
        <article class="blog-empty-state cv-empty-state">
            <h2>No public CV selected</h2>
            <p>No CV build has been stored as the publicly accessible CV yet.</p>
        </article>
    <?php else: ?>
        <div class="cv-download-actions">
            <button class="portfolio-reset-button" type="button" onclick="window.print()">Download / Print Current CV</button>
        </div>

        <section class="cv-preview-shell">
            <?= pmRenderCvHtml($build) ?>
        </section>
    <?php endif; ?>
</section>
