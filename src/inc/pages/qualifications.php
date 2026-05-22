<?php
/**
 * Public qualifications page.
 *
 * Formal and informal credentials are deliberately separated so the public page
 * reads more like a structured credentials page than a generic card dump. The
 * page is wrapped in one container because the global <main> element uses flex.
 */

declare(strict_types=1);

$qualifications = pmGetQualifications(false);
$formalQualifications = [];
$informalQualifications = [];

foreach ($qualifications as $qualification) {
    $type = strtolower(trim((string) ($qualification['qualification_type'] ?? 'formal')));

    if ($type === 'informal') {
        $informalQualifications[] = $qualification;
        continue;
    }

    $formalQualifications[] = $qualification;
}

/**
 * Renders one public qualification card.
 *
 * This local helper keeps the Formal and Informal sections using identical card
 * markup without duplicating the full HTML structure in two loops.
 *
 * @param array<string, mixed> $qualification Qualification database row.
 * @return void
 */
function pmRenderPublicQualificationCard(array $qualification): void
{
    ?>
    <article class="qualification-card public-qualification-card">
        <div class="qualification-card-header">
            <div>
                <p class="admin-kicker"><?= pmEscape($qualification['qualification_type'] ?: 'Qualification') ?></p>
                <h2><?= pmEscape($qualification['title']) ?></h2>
            </div>
            <?php if (!empty($qualification['credential_url'])): ?>
                <a class="button button-secondary" href="<?= pmEscape($qualification['credential_url']) ?>" rel="noopener noreferrer" target="_blank">View credential</a>
            <?php endif; ?>
        </div>

        <p class="qualification-meta">
            <?php if (!empty($qualification['provider'])): ?>
                <span><?= pmEscape($qualification['provider']) ?></span>
            <?php endif; ?>
            <?php if (!empty($qualification['obtained_date'])): ?>
                <span>Obtained <?= pmEscape($qualification['obtained_date']) ?></span>
            <?php endif; ?>
            <?php if (!empty($qualification['expiry_date'])): ?>
                <span>Expires <?= pmEscape($qualification['expiry_date']) ?></span>
            <?php endif; ?>
        </p>

        <?php if (!empty($qualification['description'])): ?>
            <p><?= nl2br(pmEscape($qualification['description'])) ?></p>
        <?php endif; ?>
    </article>
    <?php
}
?>
<section class="qualifications-page public-qualifications-page">
    <header class="qualifications-header">
        <p class="admin-kicker">Credentials</p>
        <h1>Qualifications</h1>
        <p>Formal and informal qualifications, certificates, and relevant credentials.</p>
    </header>

    <?php if ($qualifications === []): ?>
        <article class="qualification-empty-state">
            <h2>No qualifications have been published yet.</h2>
            <p>Add qualifications in the admin area, mark them as public, and they will appear here.</p>
        </article>
    <?php endif; ?>

    <?php if ($formalQualifications !== []): ?>
        <section class="qualification-section" aria-labelledby="formal-qualifications-heading">
            <div class="qualification-section-heading">
                <p class="admin-kicker">Formal</p>
                <h2 id="formal-qualifications-heading">Formal Qualifications</h2>
            </div>
            <div class="qualifications-list qualifications-list-split">
                <?php foreach ($formalQualifications as $qualification): ?>
                    <?php pmRenderPublicQualificationCard($qualification); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($informalQualifications !== []): ?>
        <section class="qualification-section" aria-labelledby="informal-qualifications-heading">
            <div class="qualification-section-heading">
                <p class="admin-kicker">Informal</p>
                <h2 id="informal-qualifications-heading">Informal Qualifications</h2>
            </div>
            <div class="qualifications-list qualifications-list-split">
                <?php foreach ($informalQualifications as $qualification): ?>
                    <?php pmRenderPublicQualificationCard($qualification); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
