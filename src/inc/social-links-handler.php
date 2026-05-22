<?php
/**
 * Site Management handler for Portfolio Manager social profile links.
 *
 * Social links are kept separate from general site settings because they can be
 * added, updated, hidden, or deleted independently from module/theme changes.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmRequirePermission('can_manage_site_settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=site-management&status=social-invalid');
}

$action = pmString($_POST['social_action'] ?? '');

/**
 * Redirects back to Site Management with a social-link status code.
 *
 * @param string $status Status query value.
 * @return never
 */
function pmSocialLinksRedirect(string $status): never
{
    pmRedirect('../../index.php?page=site-management&status=' . rawurlencode($status));
}

/**
 * Stores a social icon upload in src/icons/socials and returns its database path.
 *
 * @param array<string, mixed>|null $file Uploaded file array.
 * @param string $label Label used to create a readable managed filename.
 * @return string Stored path such as icons/socials/custom-social-example-a1b2c3.svg.
 */
function pmStoreSocialIconUpload(?array $file, string $label): string
{
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Social icon upload failed.');
    }
    // Filessize check before mime/content checks to avoid processing large files.
    if ((int) ($file['size'] ?? 0) > 1024 * 1024) {
        throw new RuntimeException('Social icon is larger than 1 MB.');
    }

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    // Will need to enforce white/black filter rules to be just SVG's in the future as I don't think this works very well with PNG images
    if (!in_array($extension, ['svg', 'png'], true)) {
        throw new RuntimeException('Social icon must be an SVG or PNG file.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $mime = is_file($tmpName) ? (mime_content_type($tmpName) ?: '') : '';
    $allowedMime = $extension === 'svg'
        ? ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml']
        : ['image/png'];

    if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Social icon MIME type is not allowed.');
    }

    $slug = pmSocialKeyFromLabel($label);
    $filename = 'custom-social-' . $slug . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'socials';

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        throw new RuntimeException('Could not create icon directory.');
    }

    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Could not save social icon.');
    }

    return 'icons/socials/' . $filename;
}

/**
 * Returns a safe icon filter value for social media SVG/PNG icons.
 *
 * The CSS exposes --svg-white and --svg-black filters. Keeping the stored value
 * to a small allow-list avoids users saving arbitrary class names or CSS.
 *
 * @param mixed $value Posted filter value.
 * @return string Either white or black.
 */
function pmSocialIconFilterValue(mixed $value): string
{
    return (string) $value === 'black' ? 'black' : 'white';
}

try {

    if ($action === 'save_social_order') {
        $orderedIds = $_POST['social_order'] ?? [];

        if (!is_array($orderedIds)) {
            pmSocialLinksRedirect('social-invalid');
        }

        $stmt = pmDb()->prepare('UPDATE social_links SET display_order = :display_order, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $order = 10;

        foreach ($orderedIds as $rawId) {
            $id = (int) $rawId;

            if ($id <= 0) {
                continue;
            }

            $stmt->execute([
                ':id' => $id,
                ':display_order' => $order,
            ]);
            $order += 10;
        }

        pmSocialLinksRedirect('social-saved');
    }

    if ($action === 'add_social_link') {
        $label = pmString($_POST['platform_label'] ?? '');
        $url = pmString($_POST['profile_url'] ?? '');

        if ($label === '') {
            pmSocialLinksRedirect('social-invalid');
        }

        $key = pmSocialKeyFromLabel($label);
        $iconPath = pmStoreSocialIconUpload($_FILES['social_icon'] ?? null, $label);
        $order = max(0, (int) ($_POST['display_order'] ?? 500));
        $iconFilter = pmSocialIconFilterValue($_POST['icon_filter'] ?? 'white');

        $stmt = pmDb()->prepare(<<<'SQL'
            INSERT INTO social_links (platform_key, platform_label, profile_url, icon_path, icon_filter, show_in_footer, show_on_contact_page, is_active, display_order, is_default, updated_at)
            VALUES (:key, :label, :url, :icon_path, :icon_filter, :footer, :contact, :active, :display_order, 0, CURRENT_TIMESTAMP)
            ON CONFLICT(platform_key) DO UPDATE SET
                platform_label = excluded.platform_label,
                profile_url = excluded.profile_url,
                icon_path = CASE WHEN excluded.icon_path = '' THEN social_links.icon_path ELSE excluded.icon_path END,
                icon_filter = excluded.icon_filter,
                show_in_footer = excluded.show_in_footer,
                show_on_contact_page = excluded.show_on_contact_page,
                is_active = excluded.is_active,
                display_order = excluded.display_order,
                updated_at = CURRENT_TIMESTAMP
        SQL);

        $stmt->execute([
            ':key' => $key,
            ':label' => $label,
            ':url' => $url,
            ':icon_path' => $iconPath,
            ':icon_filter' => $iconFilter,
            ':footer' => isset($_POST['show_in_footer']) ? 1 : 0,
            ':contact' => isset($_POST['show_on_contact_page']) ? 1 : 0,
            ':active' => isset($_POST['is_active']) ? 1 : 0,
            ':display_order' => $order,
        ]);

        pmSocialLinksRedirect('social-saved');
    }

    if ($action === 'update_social_link') {
        $id = (int) ($_POST['social_id'] ?? 0);
        $label = pmString($_POST['platform_label'] ?? '');
        $url = pmString($_POST['profile_url'] ?? '');

        if ($id <= 0 || $label === '') {
            pmSocialLinksRedirect('social-invalid');
        }

        $current = pmDb()->prepare('SELECT icon_path FROM social_links WHERE id = :id LIMIT 1');
        $current->execute([':id' => $id]);
        $currentIcon = (string) ($current->fetchColumn() ?: '');
        $newIcon = pmStoreSocialIconUpload($_FILES['social_icon'] ?? null, $label);
        $iconPath = $newIcon !== '' ? $newIcon : $currentIcon;
        $order = max(0, (int) ($_POST['display_order'] ?? 0));
        $iconFilter = pmSocialIconFilterValue($_POST['icon_filter'] ?? 'white');

        $stmt = pmDb()->prepare(<<<'SQL'
            UPDATE social_links
            SET platform_label = :label,
                profile_url = :url,
                icon_path = :icon_path,
                icon_filter = :icon_filter,
                show_in_footer = :footer,
                show_on_contact_page = :contact,
                is_active = :active,
                display_order = :display_order,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        SQL);

        $stmt->execute([
            ':id' => $id,
            ':label' => $label,
            ':url' => $url,
            ':icon_path' => $iconPath,
            ':icon_filter' => $iconFilter,
            ':footer' => isset($_POST['show_in_footer']) ? 1 : 0,
            ':contact' => isset($_POST['show_on_contact_page']) ? 1 : 0,
            ':active' => isset($_POST['is_active']) ? 1 : 0,
            ':display_order' => $order,
        ]);

        pmSocialLinksRedirect('social-saved');
    }

    if ($action === 'delete_social_link') {
        $id = (int) ($_POST['social_id'] ?? 0);

        if ($id <= 0) {
            pmSocialLinksRedirect('social-invalid');
        }

        $row = pmDb()->prepare('SELECT is_default FROM social_links WHERE id = :id LIMIT 1');
        $row->execute([':id' => $id]);
        $isDefault = (int) ($row->fetchColumn() ?: 0) === 1;

        if ($isDefault) {
            $stmt = pmDb()->prepare('UPDATE social_links SET profile_url = \'\', show_in_footer = 0, show_on_contact_page = 0, is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = pmDb()->prepare('DELETE FROM social_links WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        pmSocialLinksRedirect('social-deleted');
    }
} catch (Throwable $error) {
    error_log('Portfolio Manager social link handler error: ' . $error->getMessage());
    pmSocialLinksRedirect('social-invalid');
}

pmSocialLinksRedirect('social-invalid');
