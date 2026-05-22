<?php
/**
 * CV Builder handler.
 * 
 * This needs a lot of work and will be where most of my attention goes
 * in the next release. For now this has minimum functionality but will be broadened out
 * later down the track if it becomes a feature that people really want
 * 
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmRequirePermission('can_manage_cv');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=manage-cv&status=invalid');
}

$action = pmString($_POST['action'] ?? 'profile');

if ($action === 'profile') {
    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO cv_profile (id, full_name, headline, email, phone, location, website, linkedin, summary, updated_at)
        VALUES (1, :full_name, :headline, :email, :phone, :location, :website, :linkedin, :summary, CURRENT_TIMESTAMP)
        ON CONFLICT(id) DO UPDATE SET
            full_name = excluded.full_name,
            headline = excluded.headline,
            email = excluded.email,
            phone = excluded.phone,
            location = excluded.location,
            website = excluded.website,
            linkedin = excluded.linkedin,
            summary = excluded.summary,
            updated_at = CURRENT_TIMESTAMP
    SQL);
    $stmt->execute([
        ':full_name' => pmString($_POST['full_name'] ?? ''),
        ':headline' => pmString($_POST['headline'] ?? ''),
        ':email' => pmString($_POST['email'] ?? ''),
        ':phone' => pmString($_POST['phone'] ?? ''),
        ':location' => pmString($_POST['location'] ?? ''),
        ':website' => pmString($_POST['website'] ?? ''),
        ':linkedin' => pmString($_POST['linkedin'] ?? ''),
        ':summary' => pmString($_POST['summary'] ?? ''),
    ]);
    pmRedirect('../../index.php?page=manage-cv&status=profile-saved');
}

if ($action === 'job') {
    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO cv_jobs (employer_name, role_title, employment_type, location, start_month, start_year, end_month, end_year, is_current, summary, display_order)
        VALUES (:employer_name, :role_title, :employment_type, :location, :start_month, :start_year, :end_month, :end_year, :is_current, :summary, :display_order)
    SQL);
    $stmt->execute([
        ':employer_name' => pmString($_POST['employer_name'] ?? ''),
        ':role_title' => pmString($_POST['role_title'] ?? ''),
        ':employment_type' => pmString($_POST['employment_type'] ?? ''),
        ':location' => pmString($_POST['location'] ?? ''),
        ':start_month' => pmString($_POST['start_month'] ?? ''),
        ':start_year' => pmString($_POST['start_year'] ?? ''),
        ':end_month' => pmString($_POST['end_month'] ?? ''),
        ':end_year' => pmString($_POST['end_year'] ?? ''),
        ':is_current' => isset($_POST['is_current']) ? 1 : 0,
        ':summary' => pmString($_POST['summary'] ?? ''),
        ':display_order' => (int) ($_POST['display_order'] ?? 0),
    ]);
    pmRedirect('../../index.php?page=manage-cv&status=job-added');
}

if ($action === 'skill') {
    $stmt = pmDb()->prepare('INSERT INTO cv_skills (skill_name, skill_group, description, is_visible, display_order) VALUES (:skill_name, :skill_group, :description, :is_visible, :display_order)');
    $stmt->execute([
        ':skill_name' => pmString($_POST['skill_name'] ?? ''),
        ':skill_group' => pmString($_POST['skill_group'] ?? 'General'),
        ':description' => pmString($_POST['description'] ?? ''),
        ':is_visible' => isset($_POST['is_visible']) ? 1 : 0,
        ':display_order' => (int) ($_POST['display_order'] ?? 0),
    ]);
    pmRedirect('../../index.php?page=manage-cv&status=skill-added');
}

if ($action === 'delete-skill') {
    $skillId = (int) ($_POST['skill_id'] ?? 0);

    if ($skillId > 0) {
        // Remove any CV build references first so deleted skills do not remain
        // attached to existing public or private CV builds.
        pmDb()->prepare('DELETE FROM cv_build_items WHERE item_type = :type AND item_id = :id')->execute([
            ':type' => 'skill',
            ':id' => $skillId,
        ]);
        pmDb()->prepare('DELETE FROM cv_skills WHERE id = :id')->execute([':id' => $skillId]);
    }

    pmRedirect('../../index.php?page=manage-cv&status=skill-deleted');
}

if ($action === 'build') {
    $db = pmDb();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare('INSERT INTO cv_builds (build_name, target_role, tailored_summary, template_key, is_public) VALUES (:build_name, :target_role, :tailored_summary, :template_key, 0)');
        $stmt->execute([
            ':build_name' => pmString($_POST['build_name'] ?? 'New CV Build'),
            ':target_role' => pmString($_POST['target_role'] ?? ''),
            ':tailored_summary' => pmString($_POST['tailored_summary'] ?? ''),
            ':template_key' => pmString($_POST['template_key'] ?? 'ats_clean'),
        ]);

        $buildId = (int) $db->lastInsertId();
        $insertItem = $db->prepare('INSERT INTO cv_build_items (build_id, item_type, item_id, display_order) VALUES (:build_id, :item_type, :item_id, :display_order)');
        $groups = [
            'job_ids' => 'job',
            'skill_ids' => 'skill',
            'qualification_ids' => 'qualification',
        ];

        foreach ($groups as $postKey => $itemType) {
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST[$postKey] ?? []))));

            foreach ($ids as $order => $itemId) {
                $insertItem->execute([
                    ':build_id' => $buildId,
                    ':item_type' => $itemType,
                    ':item_id' => $itemId,
                    ':display_order' => $order,
                ]);
            }
        }

        $db->commit();
    } catch (Throwable $error) {
        $db->rollBack();
        error_log('Portfolio Manager CV build error: ' . $error->getMessage());
        pmRedirect('../../index.php?page=manage-cv&status=invalid');
    }

    pmRedirect('../../index.php?page=manage-cv&status=build-created');
}

if ($action === 'publish-build') {
    $buildId = (int) ($_POST['build_id'] ?? 0);
    $check = pmDb()->prepare('SELECT COUNT(*) FROM cv_builds WHERE id = :id');
    $check->execute([':id' => $buildId]);

    if ($buildId <= 0 || (int) $check->fetchColumn() === 0) {
        pmRedirect('../../index.php?page=manage-cv&status=invalid');
    }

    pmDb()->exec('UPDATE cv_builds SET is_public = 0');
    pmDb()->prepare('UPDATE cv_builds SET is_public = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id' => $buildId]);
    pmSetSiteSetting('public_cv_build_id', (string) $buildId);
    pmRedirect('../../index.php?page=manage-cv&status=public-cv-set');
}

pmRedirect('../../index.php?page=manage-cv&status=unknown');
