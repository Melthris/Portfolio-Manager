<?php
/**
 * Qualifications Management handler.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmRequirePermission('can_manage_qualifications');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=manage-qualifications&status=invalid');
}

$action = pmString($_POST['action'] ?? 'save');
$id = (int) ($_POST['id'] ?? 0);

if ($action === 'delete') {
    pmDb()->prepare('DELETE FROM qualifications WHERE id = :id')->execute([':id' => $id]);
    pmRedirect('../../index.php?page=manage-qualifications&status=deleted');
}

$title = pmString($_POST['title'] ?? '');

if ($title === '') {
    pmRedirect('../../index.php?page=manage-qualifications&status=missing-title');
}

$params = [
    ':title' => $title,
    ':provider' => pmString($_POST['provider'] ?? ''),
    ':qualification_type' => pmString($_POST['qualification_type'] ?? 'formal'),
    ':description' => pmString($_POST['description'] ?? ''),
    ':obtained_date' => pmString($_POST['obtained_date'] ?? ''),
    ':expiry_date' => pmString($_POST['expiry_date'] ?? ''),
    ':credential_url' => pmString($_POST['credential_url'] ?? ''),
    ':show_on_qualifications_page' => isset($_POST['show_on_qualifications_page']) ? 1 : 0,
    ':available_for_cv' => isset($_POST['available_for_cv']) ? 1 : 0,
    ':display_order' => (int) ($_POST['display_order'] ?? 0),
];

if ($id > 0) {
    $params[':id'] = $id;
    $stmt = pmDb()->prepare(<<<'SQL'
        UPDATE qualifications
        SET title = :title, provider = :provider, qualification_type = :qualification_type,
            description = :description, obtained_date = :obtained_date, expiry_date = :expiry_date,
            credential_url = :credential_url, show_on_qualifications_page = :show_on_qualifications_page,
            available_for_cv = :available_for_cv, display_order = :display_order, updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    SQL);
} else {
    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO qualifications (title, provider, qualification_type, description, obtained_date, expiry_date, credential_url, show_on_qualifications_page, available_for_cv, display_order)
        VALUES (:title, :provider, :qualification_type, :description, :obtained_date, :expiry_date, :credential_url, :show_on_qualifications_page, :available_for_cv, :display_order)
    SQL);
}

$stmt->execute($params);
pmRedirect('../../index.php?page=manage-qualifications&status=saved');
