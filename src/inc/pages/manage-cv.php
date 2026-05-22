<?php
/**
 * CV Builder page.
 *
 * This layout keeps the CV tools inside the same working admin sidebar and
 * workspace structure as Manage Projects, rather than using the earlier generic
 * multi-card grid that stretched controls across the page.
 */

declare(strict_types=1);

pmRequirePermission('can_manage_cv');
$profile = pmGetCvProfile();
$jobs = pmGetCvJobs();
$skills = pmGetCvSkills();
$builds = pmGetCvBuilds();
$cvQualifications = array_values(array_filter(pmGetQualifications(false), static fn (array $row): bool => (int) $row['available_for_cv'] === 1));
$statusMessages = [
    'profile-saved' => ['success', 'CV profile saved.'],
    'job-added' => ['success', 'Job added.'],
    'skill-added' => ['success', 'Skill added.'],
    'skill-deleted' => ['success', 'Skill removed.'],
    'build-created' => ['success', 'CV build created.'],
    'public-cv-set' => ['success', 'Public CV updated.'],
    'invalid' => ['error', 'Your session expired. Please try again.'],
];
$status = pmString($_GET['status'] ?? '');
$flash = $statusMessages[$status] ?? null;
?>

<section class="manage-projects-page manage-cv-page">
    <div class="manage-projects-header">
        <div>
            <p class="admin-kicker">Admin Portal</p>
            <h1 class="admin-page-title">CV Builder</h1>
            <p class="admin-page-subtitle">
                Store career history once, then compile targeted CV builds for different roles and applications.
            </p>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <p class="contact-flash contact-flash-<?= pmEscape($flash[0]) ?>" role="alert"><?= pmEscape($flash[1]) ?></p>
    <?php endif; ?>

    <div class="manage-projects-layout cv-builder-layout">
        <aside class="project-admin-sidebar cv-admin-sidebar">
            <div class="admin-panel-heading">
                <h2>CV Data</h2>
                <p>Stored information available for CV builds.</p>
            </div>

            <div class="project-admin-list cv-admin-list">
                <article class="qualification-list-card">
                    <strong><?= count($jobs) ?></strong>
                    <span>Jobs stored</span>
                </article>
                <article class="qualification-list-card">
                    <strong><?= count($skills) ?></strong>
                    <span>Skills stored</span>
                </article>

                <h3 class="sidebar-section-title">Builds</h3>
                <?php if ($builds === []): ?>
                    <p class="blog-admin-empty">No CV builds created yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($builds as $build): ?>
                            <?php $isPublicBuild = (int) $build['is_public'] === 1; ?>
                            <li>
                                <form method="post" action="src/inc/cv-handler.php" class="cv-build-list-card<?= $isPublicBuild ? ' is-public-cv-build' : '' ?>">
                                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="publish-build">
                                    <input type="hidden" name="build_id" value="<?= (int) $build['id'] ?>">
                                    <strong><?= pmEscape((string) $build['build_name']) ?></strong>
                                    <span><?= pmEscape((string) ($build['target_role'] ?: 'No target role')) ?></span>
                                    <small class="cv-public-status">
                                        <?= $isPublicBuild ? 'Public website CV' : 'Not public' ?>
                                    </small>
                                    <button class="blog-delete-button cv-publish-button" type="submit" <?= $isPublicBuild ? 'disabled' : '' ?>>
                                        <?= $isPublicBuild ? 'Currently public' : 'Make public CV' ?>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="portfolio-reset-button cv-preview-link" href="index.php?page=cv-download" target="_blank" rel="noopener noreferrer">Preview public CV</a>
                <?php endif; ?>
            </div>
        </aside>

        <section class="project-admin-workspace cv-builder-workspace">
            <article class="admin-card cv-profile-card">
                <div class="admin-card-header">
                    <div>
                        <p class="admin-kicker">Profile</p>
                        <h2>CV Profile</h2>
                    </div>
                </div>

                <form method="post" action="src/inc/cv-handler.php">
                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                    <input type="hidden" name="action" value="profile">

                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Full name</span>
                            <input class="textbox" name="full_name" value="<?= pmEscape($profile['full_name'] ?? '') ?>">
                        </label>

                        <label class="admin-field">
                            <span>Headline</span>
                            <input class="textbox" name="headline" value="<?= pmEscape($profile['headline'] ?? '') ?>">
                        </label>

                        <label class="admin-field">
                            <span>Email</span>
                            <input class="textbox" name="email" value="<?= pmEscape($profile['email'] ?? '') ?>">
                        </label>

                        <label class="admin-field">
                            <span>Phone</span>
                            <input class="textbox" name="phone" value="<?= pmEscape($profile['phone'] ?? '') ?>">
                        </label>

                        <label class="admin-field">
                            <span>Location</span>
                            <input class="textbox" name="location" value="<?= pmEscape($profile['location'] ?? '') ?>">
                        </label>

                        <label class="admin-field">
                            <span>Website</span>
                            <input class="textbox" name="website" value="<?= pmEscape($profile['website'] ?? '') ?>">
                        </label>

                        <label class="admin-field admin-field-wide">
                            <span>LinkedIn</span>
                            <input class="textbox" name="linkedin" value="<?= pmEscape($profile['linkedin'] ?? '') ?>">
                        </label>

                        <label class="admin-field admin-field-wide">
                            <span>Summary</span>
                            <textarea name="summary" rows="5"><?= pmEscape($profile['summary'] ?? '') ?></textarea>
                        </label>
                    </div>

                    <div class="admin-actions">
                        <button type="submit">Save profile</button>
                    </div>
                </form>
            </article>

            <article class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <p class="admin-kicker">Experience</p>
                        <h2>Add Job</h2>
                    </div>
                </div>

                <form method="post" action="src/inc/cv-handler.php">
                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                    <input type="hidden" name="action" value="job">

                    <div class="admin-form-grid admin-form-grid-compact">
                        <label class="admin-field"><span>Employer</span><input class="textbox" name="employer_name" required></label>
                        <label class="admin-field"><span>Role</span><input class="textbox" name="role_title" required></label>
                        <label class="admin-field"><span>Start month</span><input class="textbox" name="start_month"></label>
                        <label class="admin-field"><span>Start year</span><input class="textbox" name="start_year"></label>
                        <label class="admin-field"><span>End month</span><input class="textbox" name="end_month"></label>
                        <label class="admin-field"><span>End year</span><input class="textbox" name="end_year"></label>
                        <label class="admin-field blog-published-toggle admin-field-wide"><span>Status</span><label class="blog-checkbox-row"><input type="checkbox" name="is_current"><span>Current role</span></label></label>
                        <label class="admin-field admin-field-wide"><span>Summary</span><textarea name="summary" rows="4"></textarea></label>
                    </div>

                    <div class="admin-actions"><button type="submit">Add job</button></div>
                </form>
            </article>

            <article class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <p class="admin-kicker">Skills</p>
                        <h2>Add Skill</h2>
                    </div>
                </div>

                <form method="post" action="src/inc/cv-handler.php">
                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                    <input type="hidden" name="action" value="skill">

                    <div class="admin-form-grid admin-form-grid-compact">
                        <label class="admin-field"><span>Skill</span><input class="textbox" name="skill_name" required></label>
                        <label class="admin-field"><span>Group</span><input class="textbox" name="skill_group" value="General"></label>
                        <label class="admin-field admin-field-wide"><span>Description</span><textarea name="description" rows="3"></textarea></label>
                        <label class="admin-field blog-published-toggle admin-field-wide"><span>Visibility</span><label class="blog-checkbox-row"><input type="checkbox" name="is_visible" checked><span>Visible</span></label></label>
                    </div>

                    <div class="admin-actions"><button type="submit">Add skill</button></div>
                </form>

                <div class="cv-skill-list-panel">
                    <h3>Stored skills</h3>
                    <?php if ($skills === []): ?>
                        <p class="blog-admin-empty">No skills have been added yet.</p>
                    <?php else: ?>
                        <ul class="cv-skill-list">
                            <?php foreach ($skills as $skill): ?>
                                <li class="cv-skill-list-item">
                                    <div>
                                        <strong><?= pmEscape((string) $skill['skill_name']) ?></strong>
                                        <span><?= pmEscape((string) ($skill['skill_group'] ?: 'General')) ?><?= (int) $skill['is_visible'] === 1 ? '' : ' · hidden' ?></span>
                                    </div>
                                    <form method="post" action="src/inc/cv-handler.php" onsubmit="return confirm('Remove this skill from the stored skills list and any CV builds that use it?');">
                                        <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete-skill">
                                        <input type="hidden" name="skill_id" value="<?= (int) $skill['id'] ?>">
                                        <button class="blog-delete-button" type="submit">Remove</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <p class="admin-kicker">Build</p>
                        <h2>Create CV Build</h2>
                    </div>
                </div>

                <form method="post" action="src/inc/cv-handler.php">
                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                    <input type="hidden" name="action" value="build">

                    <div class="admin-form-grid admin-form-grid-compact">
                        <label class="admin-field"><span>Build name</span><input class="textbox" name="build_name" required></label>
                        <label class="admin-field"><span>Target role</span><input class="textbox" name="target_role"></label>
                        <label class="admin-field admin-field-wide"><span>Template</span><select class="portfolio-filter-select" name="template_key"><option value="ats_clean">ATS Clean</option><option value="portfolio_styled">Portfolio Styled</option><option value="trade">Trade / Blue Collar</option><option value="software">Software / White Collar</option></select></label>
                        <label class="admin-field admin-field-wide"><span>Tailored summary</span><textarea name="tailored_summary" rows="4"></textarea></label>
                    </div>

                    <div class="cv-build-picker-grid">
                        <fieldset class="cv-build-picker">
                            <legend>Jobs to include</legend>
                            <?php if ($jobs === []): ?>
                                <p class="blog-admin-empty">Add jobs first.</p>
                            <?php else: ?>
                                <?php foreach ($jobs as $job): ?>
                                    <label class="blog-checkbox-row">
                                        <input type="checkbox" name="job_ids[]" value="<?= (int) $job['id'] ?>" checked>
                                        <span><?= pmEscape((string) $job['role_title']) ?> · <?= pmEscape((string) $job['employer_name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </fieldset>

                        <fieldset class="cv-build-picker">
                            <legend>Skills to include</legend>
                            <?php if ($skills === []): ?>
                                <p class="blog-admin-empty">Add skills first.</p>
                            <?php else: ?>
                                <?php foreach ($skills as $skill): ?>
                                    <label class="blog-checkbox-row">
                                        <input type="checkbox" name="skill_ids[]" value="<?= (int) $skill['id'] ?>" <?= (int) $skill['is_visible'] === 1 ? 'checked' : '' ?>>
                                        <span><?= pmEscape((string) $skill['skill_name']) ?> · <?= pmEscape((string) ($skill['skill_group'] ?: 'General')) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </fieldset>

                        <fieldset class="cv-build-picker cv-build-picker-wide">
                            <legend>Qualifications to include</legend>
                            <?php if ($cvQualifications === []): ?>
                                <p class="blog-admin-empty">No CV-ready qualifications are published yet.</p>
                            <?php else: ?>
                                <?php foreach ($cvQualifications as $qualification): ?>
                                    <label class="blog-checkbox-row">
                                        <input type="checkbox" name="qualification_ids[]" value="<?= (int) $qualification['id'] ?>" checked>
                                        <span><?= pmEscape((string) $qualification['title']) ?> · <?= pmEscape((string) ($qualification['qualification_type'] ?: 'qualification')) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </fieldset>
                    </div>

                    <div class="admin-actions"><button type="submit">Create build</button></div>
                </form>
            </article>
        </section>
    </div>
</section>
