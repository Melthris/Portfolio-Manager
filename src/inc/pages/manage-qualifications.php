<?php
/**
 * Qualifications Management page.
 *
 * The page is intentionally built on the same admin shell as Manage Projects so
 * it inherits the working sidebar/workspace proportions and does not fall back
 * to the broken generic card grid.
 */

declare(strict_types=1);

pmRequirePermission('can_manage_qualifications');
$qualifications = pmGetQualifications(true);
$statusMessages = [
    'saved' => ['success', 'Qualification saved.'],
    'deleted' => ['success', 'Qualification deleted.'],
    'invalid' => ['error', 'Your session expired. Please try again.'],
    'missing-title' => ['error', 'A qualification title is required.'],
];
$status = pmString($_GET['status'] ?? '');
$flash = $statusMessages[$status] ?? null;
?>

<section class="manage-projects-page manage-qualifications-page">
    <div class="manage-projects-header">
        <div>
            <p class="admin-kicker">Admin Portal</p>
            <h1 class="admin-page-title">Manage Qualifications</h1>
            <p class="admin-page-subtitle">
                Add qualifications once and reuse them on the public Qualifications page and inside CV builds.
            </p>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <p class="contact-flash contact-flash-<?= pmEscape($flash[0]) ?>" role="alert"><?= pmEscape($flash[1]) ?></p>
    <?php endif; ?>

    <div class="manage-projects-layout">
        <aside class="project-admin-sidebar qualification-admin-sidebar">
            <div class="admin-panel-heading">
                <h2>Existing</h2>
                <p>Review current qualifications and remove entries that should no longer be stored.</p>
            </div>

            <div class="project-admin-list qualification-admin-list">
                <?php if ($qualifications === []): ?>
                    <p class="blog-admin-empty">No qualifications stored yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($qualifications as $qualification): ?>
                            <li>
                                <article class="qualification-list-card">
                                    <strong><?= pmEscape((string) $qualification['title']) ?></strong>
                                    <span><?= pmEscape((string) ($qualification['provider'] ?: 'No provider listed')) ?></span>
                                    <small>
                                        <?= (int) $qualification['show_on_qualifications_page'] === 1 ? 'Public' : 'Hidden' ?> ·
                                        <?= (int) $qualification['available_for_cv'] === 1 ? 'CV ready' : 'Not used in CV' ?>
                                    </small>

                                    <form method="post" action="src/inc/qualifications-handler.php" onsubmit="return confirm('Delete this qualification?');">
                                        <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $qualification['id'] ?>">
                                        <button class="blog-delete-button" type="submit">Delete</button>
                                    </form>
                                </article>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>

        <section class="project-admin-workspace">
            <article class="admin-card qualification-form-card">
                <div class="admin-card-header">
                    <div>
                        <p class="admin-kicker">New Qualification</p>
                        <h2>Add Qualification</h2>
                    </div>
                </div>

                <form method="post" action="src/inc/qualifications-handler.php">
                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                    <input type="hidden" name="action" value="save">

                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Title</span>
                            <input class="textbox" name="title" required>
                        </label>

                        <label class="admin-field">
                            <span>Provider</span>
                            <input class="textbox" name="provider">
                        </label>

                        <label class="admin-field">
                            <span>Type</span>
                            <select class="portfolio-filter-select" name="qualification_type">
                                <option value="formal">Formal</option>
                                <option value="informal">Informal</option>
                                <option value="certificate">Certificate</option>
                                <option value="licence">Licence / Ticket</option>
                            </select>
                        </label>

                        <label class="admin-field">
                            <span>Display order</span>
                            <input class="textbox" name="display_order" type="number" value="0">
                        </label>

                        <label class="admin-field">
                            <span>Obtained date</span>
                            <input class="textbox" name="obtained_date" type="date">
                        </label>

                        <label class="admin-field">
                            <span>Expiry date</span>
                            <input class="textbox" name="expiry_date" type="date">
                        </label>

                        <label class="admin-field admin-field-wide">
                            <span>Credential URL</span>
                            <input class="textbox" name="credential_url" type="url">
                        </label>

                        <label class="admin-field admin-field-wide">
                            <span>Description</span>
                            <textarea name="description" rows="5"></textarea>
                        </label>

                        <label class="admin-field blog-published-toggle">
                            <span>Public display</span>
                            <label class="blog-checkbox-row">
                                <input type="checkbox" name="show_on_qualifications_page" checked>
                                <span>Show publicly</span>
                            </label>
                        </label>

                        <label class="admin-field blog-published-toggle">
                            <span>CV usage</span>
                            <label class="blog-checkbox-row">
                                <input type="checkbox" name="available_for_cv" checked>
                                <span>Available for CV</span>
                            </label>
                        </label>
                    </div>

                    <div class="admin-actions">
                        <button type="submit">Save qualification</button>
                    </div>
                </form>
            </article>
        </section>
    </div>
</section>
