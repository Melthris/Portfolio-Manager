<?php
/**
 * Outputs the active technology catalogue for JavaScript screens.
 *
 * This is the browser-side mirror of the SQLite-backed catalogue returned by
 * pmTechCatalogue(). Project rendering, project management, blog rendering, and
 * blog management all read these globals instead of keeping separate hardcoded
 * technology/icon/category arrays.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

header('Content-Type: application/javascript; charset=UTF-8');

$icons = [];
$categoriesByLabel = [];
$labelToKey = [];
$catalogue = [];

foreach (pmTechCatalogue() as $key => $item) {
    $label = (string) $item['label'];
    $category = (string) $item['category'];
    $icon = $item['icon'];
    $iconPath = pmTechIconPath($icon !== null ? (string) $icon : null);

    $icons[$label] = $iconPath;
    $categoriesByLabel[$label] = $category;
    $labelToKey[$label] = (string) $key;
    $labelToKey[pmTechKeyFromLabel($label)] = (string) $key;
    $catalogue[(string) $key] = [
        'label' => $label,
        'category' => $category,
        'icon' => $iconPath,
    ];
}
?>
/**
 * Active Portfolio Manager technology catalogue.
 *
 * These globals are generated from SQLite through PHP. Do not duplicate the
 * catalogue in page JavaScript; consume these values so all screens stay in
 * sync with Site Management.
 */
var techIcons = <?= json_encode($icons, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
window.portfolioManagerTechIcons = techIcons;
window.portfolioManagerTechCategories = <?= json_encode(pmTechCategories(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
window.portfolioManagerTechCatalogue = <?= json_encode($catalogue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
window.portfolioManagerTechCategoryMap = <?= json_encode($categoriesByLabel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
window.portfolioManagerTechLabelToKey = <?= json_encode($labelToKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
