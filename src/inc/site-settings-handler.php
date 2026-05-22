<?php
/**
 * Site Management handler for module visibility, site identity, and theme settings.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmRequirePermission('can_manage_site_settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=site-management&status=invalid');
}

$settingsAction = pmString($_POST['settings_action'] ?? 'save', 'save');

if ($settingsAction === 'restore_defaults') {
    pmRestoreDefaultSiteManagementSettings();
    pmRedirect('../../index.php?page=site-management&status=restored');
}

$homeDefaults = pmDefaultHomeContent();
pmSetSiteSetting('site_title', pmString($_POST['site_title'] ?? PM_APP_NAME, PM_APP_NAME));
pmSetSiteSetting('home_subheading', pmString($_POST['home_subheading'] ?? $homeDefaults['subheading'], $homeDefaults['subheading']));
pmSetSiteSetting('home_body_text', pmString($_POST['home_body_text'] ?? $homeDefaults['body'], $homeDefaults['body']));

$contactDefaults = pmDefaultContactContent();
pmSetSiteSetting('contact_heading', pmString($_POST['contact_heading'] ?? $contactDefaults['heading'], $contactDefaults['heading']));
pmSetSiteSetting('contact_body', pmString($_POST['contact_body'] ?? $contactDefaults['body'], $contactDefaults['body']));

for ($cardIndex = 1; $cardIndex <= 3; $cardIndex++) {
    $fallbackCard = $contactDefaults['cards'][$cardIndex] ?? ['label' => '', 'text' => '', 'enabled' => 0];
    $postedCard = is_array($_POST['contact_cards'][$cardIndex] ?? null) ? $_POST['contact_cards'][$cardIndex] : [];

    pmSetSiteSetting('contact_card_' . $cardIndex . '_enabled', isset($postedCard['enabled']) ? '1' : '0');
    pmSetSiteSetting('contact_card_' . $cardIndex . '_label', pmString($postedCard['label'] ?? $fallbackCard['label'], $fallbackCard['label']));
    pmSetSiteSetting('contact_card_' . $cardIndex . '_text', pmString($postedCard['text'] ?? $fallbackCard['text'], $fallbackCard['text']));
}

$moduleStmt = pmDb()->prepare(<<<'SQL'
    UPDATE site_modules
    SET is_enabled = :enabled, is_public = :public, updated_at = CURRENT_TIMESTAMP
    WHERE module_key = :key
SQL);

foreach (array_keys(pmDefaultSiteModules()) as $module) {
    $moduleStmt->execute([
        ':enabled' => isset($_POST['module_enabled'][$module]) ? 1 : 0,
        ':public' => isset($_POST['module_public'][$module]) ? 1 : 0,
        ':key' => $module,
    ]);
}

$themeStmt = pmDb()->prepare(<<<'SQL'
    INSERT INTO theme_settings (setting_key, setting_value, updated_by_user_id, updated_at)
    VALUES (:key, :value, :user_id, CURRENT_TIMESTAMP)
    ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_by_user_id = excluded.updated_by_user_id, updated_at = CURRENT_TIMESTAMP
SQL);

foreach (array_keys(pmDefaultThemeVariables()) as $key) {
    $value = pmString($_POST['theme'][$key] ?? '');

    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1) {
        $themeStmt->execute([':key' => $key, ':value' => $value, ':user_id' => pmCurrentUserId()]);
    }
}

pmRedirect('../../index.php?page=site-management&status=saved');
