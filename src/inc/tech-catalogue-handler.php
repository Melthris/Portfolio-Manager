<?php
/**
 * Handles technology catalogue actions from Site Management.
 *
 * The `tech_items` table is the single editable source for default and custom
 * technology records. Default records can be hidden, relabelled, recategorised,
 * and given replacement icons. Custom records can be added, updated, or removed.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmRequirePermission('can_manage_site_settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=site-management&status=invalid');
}

$action = pmString($_POST['tech_action'] ?? '');

if ($action === 'add_tech_item' || $action === 'add_custom_tech') {
    pmCreateTechnologyItem();
    pmRedirect('../../index.php?page=site-management&status=tech-added');
}

if ($action === 'update_tech_item') {
    pmUpdateTechnologyItem((int) ($_POST['tech_id'] ?? 0));
    pmRedirect('../../index.php?page=site-management&status=tech-updated');
}

if ($action === 'delete_tech_item' || $action === 'delete_custom_tech') {
    pmDeleteTechnologyItem((int) ($_POST['tech_id'] ?? 0));
    pmRedirect('../../index.php?page=site-management&status=tech-deleted');
}

pmRedirect('../../index.php?page=site-management&status=invalid');

/**
 * Creates a new custom technology catalogue item.
 *
 * New records are stored directly in `tech_items` with `is_default = 0`, which
 * keeps custom and shipped technologies in the same catalogue source used by
 * all project and blog screens.
 *
 * @return void
 */
function pmCreateTechnologyItem(): void
{
    $label = pmString($_POST['custom_tech_label'] ?? '');
    $category = pmString($_POST['custom_tech_category'] ?? 'misc', 'misc');

    if ($label === '' || !array_key_exists($category, pmTechCategories())) {
        pmRedirect('../../index.php?page=site-management&status=tech-invalid');
    }

    $key = pmUniqueTechKey(pmTechKeyFromLabel($label));
    $iconPath = pmStoreTechnologyIcon($key, 'custom_tech_icon');
    $displayOrder = pmNextTechDisplayOrder($category);

    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO tech_items (
            tech_key,
            label,
            category,
            icon_path,
            is_default,
            is_active,
            display_order,
            created_by_user_id,
            updated_at
        ) VALUES (
            :tech_key,
            :label,
            :category,
            :icon_path,
            0,
            1,
            :display_order,
            :user_id,
            CURRENT_TIMESTAMP
        )
    SQL);

    $stmt->execute([
        ':tech_key' => $key,
        ':label' => $label,
        ':category' => $category,
        ':icon_path' => $iconPath,
        ':display_order' => $displayOrder,
        ':user_id' => pmCurrentUserId(),
    ]);
}

/**
 * Updates an existing default or custom technology item.
 *
 * The tech key is intentionally stable. Projects and blog posts store visible
 * labels, so the key is only used internally for catalogue identity and icon
 * lookup continuity.
 *
 * @param int $techId Technology row ID.
 * @return void
 */
function pmUpdateTechnologyItem(int $techId): void
{
    if ($techId <= 0) {
        pmRedirect('../../index.php?page=site-management&status=tech-invalid');
    }

    $row = pmFindTechnologyItem($techId);

    if ($row === null) {
        pmRedirect('../../index.php?page=site-management&status=tech-invalid');
    }

    $label = pmString($_POST['tech_label'] ?? '');
    $category = pmString($_POST['tech_category'] ?? 'misc', 'misc');
    $isActive = isset($_POST['tech_is_active']) ? 1 : 0;

    if ($label === '' || !array_key_exists($category, pmTechCategories())) {
        pmRedirect('../../index.php?page=site-management&status=tech-invalid');
    }

    $existingIcon = (string) ($row['icon_path'] ?? '');
    $newIcon = pmStoreTechnologyIcon((string) ($row['tech_key'] ?? 'tech'), 'tech_icon');
    $iconPath = $newIcon !== '' ? $newIcon : $existingIcon;

    $stmt = pmDb()->prepare(<<<'SQL'
        UPDATE tech_items
        SET label = :label,
            category = :category,
            icon_path = :icon_path,
            is_active = :is_active,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    SQL);

    $stmt->execute([
        ':label' => $label,
        ':category' => $category,
        ':icon_path' => $iconPath,
        ':is_active' => $isActive,
        ':id' => $techId,
    ]);

    if ($newIcon !== '' && $existingIcon !== '' && $existingIcon !== $newIcon && pmIsManagedTechnologyIconPath($existingIcon)) {
        pmDeleteUploadedTechIcon($existingIcon);
    }
}

/**
 * Deletes a custom technology or hides a default technology.
 *
 * Default catalogue records are not physically removed because they are part of
 * the shipped package and may be restored by ticking Visible again. Custom rows
 * are deleted because they belong entirely to the local site owner.
 *
 * @param int $techId Technology row ID.
 * @return void
 */
function pmDeleteTechnologyItem(int $techId): void
{
    if ($techId <= 0) {
        return;
    }

    $row = pmFindTechnologyItem($techId);

    if ($row === null) {
        return;
    }

    if ((int) ($row['is_default'] ?? 0) === 1) {
        $stmt = pmDb()->prepare('UPDATE tech_items SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([':id' => $techId]);
        return;
    }

    $delete = pmDb()->prepare('DELETE FROM tech_items WHERE id = :id');
    $delete->execute([':id' => $techId]);

    $iconPath = (string) ($row['icon_path'] ?? '');

    if ($iconPath !== '' && pmIsManagedTechnologyIconPath($iconPath)) {
        pmDeleteUploadedTechIcon($iconPath);
    }
}

/**
 * Loads a single technology row by ID.
 *
 * @param int $techId Technology row ID.
 * @return array<string, mixed>|null Technology row, or null if not found.
 */
function pmFindTechnologyItem(int $techId): ?array
{
    $stmt = pmDb()->prepare('SELECT * FROM tech_items WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $techId]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

/**
 * Returns a unique catalogue key for a new custom technology label.
 *
 * @param string $baseKey Normalised base key.
 * @return string Unique technology key.
 */
function pmUniqueTechKey(string $baseKey): string
{
    $baseKey = $baseKey !== '' && $baseKey !== 'misc' ? $baseKey : 'customtech';
    $key = $baseKey;
    $counter = 2;

    $stmt = pmDb()->prepare('SELECT COUNT(*) FROM tech_items WHERE tech_key = :tech_key COLLATE NOCASE');

    while (true) {
        $stmt->execute([':tech_key' => $key]);

        if ((int) $stmt->fetchColumn() === 0) {
            return $key;
        }

        $key = $baseKey . $counter;
        $counter++;
    }
}

/**
 * Calculates the next display order inside a category.
 *
 * @param string $category Technology category key.
 * @return int Next display order value.
 */
function pmNextTechDisplayOrder(string $category): int
{
    $stmt = pmDb()->prepare('SELECT COALESCE(MAX(display_order), 0) + 10 FROM tech_items WHERE category = :category');
    $stmt->execute([':category' => $category]);

    return (int) $stmt->fetchColumn();
}

/**
 * Stores an uploaded technology icon in the same folder as shipped tech icons.
 *
 * Custom and replacement icons are written to src/icons with a custom-tech-
 * filename prefix. The prefix lets the delete/update handlers remove only
 * owner-managed artwork while leaving package icons such as PHP.svg untouched.
 *
 * @param string $techKey Technology key used in the stored filename.
 * @param string $fieldName File input name.
 * @return string Relative icon path, or an empty string if no valid icon was uploaded.
 */
function pmStoreTechnologyIcon(string $techKey, string $fieldName): string
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return '';
    }

    $file = $_FILES[$fieldName];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 1024 * 1024) {
        return '';
    }

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, ['svg', 'png'], true)) {
        return '';
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return '';
    }

    $iconDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'icons';

    if (!is_dir($iconDir) && !mkdir($iconDir, 0750, true)) {
        return '';
    }

    $safeKey = pmTechKeyFromLabel($techKey);
    $filename = 'custom-tech-' . $safeKey . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = $iconDir . DIRECTORY_SEPARATOR . $filename;

    if ($extension === 'svg') {
        $svg = file_get_contents($tmpPath);

        if (!is_string($svg) || stripos($svg, '<svg') === false) {
            return '';
        }

        $svg = pmSanitiseUploadedSvg($svg);
        file_put_contents($destination, $svg, LOCK_EX);
    } else {
        move_uploaded_file($tmpPath, $destination);
    }

    return 'icons/' . $filename;
}

/**
 * Checks whether an icon path belongs to user-managed technology artwork.
 *
 * Built-in icons are ignored so default package assets cannot be deleted when
 * a user replaces an icon. Legacy uploads/tech-icons paths are still recognised
 * so older staged installs can clean up files after migration or replacement.
 *
 * @param string $iconPath Stored technology icon path.
 * @return bool True when the file is safe for the app to delete.
 */
function pmIsManagedTechnologyIconPath(string $iconPath): bool
{
    return str_starts_with($iconPath, 'icons/custom-tech-')
        || str_starts_with($iconPath, 'uploads/tech-icons/');
}

/**
 * Deletes a previously uploaded technology icon.
 *
 * @param string $iconPath Stored icon path.
 * @return void
 */
function pmDeleteUploadedTechIcon(string $iconPath): void
{
    if (!pmIsManagedTechnologyIconPath($iconPath)) {
        return;
    }

    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $iconPath);

    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

/**
 * Performs lightweight sanitisation for uploaded SVG icon files.
 *
 * This is intentionally conservative for a public self-hosted project. It strips
 * script tags, foreignObject blocks, javascript: URLs, and inline event handlers.
 *
 * @param string $svg Raw SVG file content.
 * @return string Sanitised SVG content.
 */
function pmSanitiseUploadedSvg(string $svg): string
{
    $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg) ?? '';
    $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg) ?? $svg;
    $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg) ?? $svg;
    $svg = preg_replace('/javascript\s*:/i', '', $svg) ?? $svg;

    return $svg;
}
