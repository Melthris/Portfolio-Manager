<?php
/**
 * Shared Portfolio Manager helper, routing, content, and module functions.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tech-defaults.php';
require_once __DIR__ . '/auth.php';

/**
 * Redirects to a new URL and stops the request.
 *
 * @param string $location Relative or absolute URL.
 * @return never
 */
function pmRedirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

/**
 * Sends common no-cache headers for admin and auth-sensitive endpoints.
 *
 * @return void
 */
function pmSendNoCacheHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/**
 * Sends a JSON response and stops the request.
 *
 * @param array<string, mixed> $payload JSON response payload.
 * @param int $statusCode HTTP status code.
 * @return never
 */
function pmJsonResponse(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Returns a string from a mixed input value.
 *
 * @param mixed $value Input value.
 * @param string $fallback Value returned when input is not scalar.
 * @return string Trimmed string.
 */
function pmString(mixed $value, string $fallback = ''): string
{
    return is_scalar($value) ? trim((string) $value) : $fallback;
}

/**
 * Returns a unique list of non-empty strings.
 *
 * @param mixed $value Input array.
 * @return array<int, string> Cleaned string list.
 */
function pmStringList(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }

    $items = array_map(static fn (mixed $item): string => pmString($item), $value);
    $items = array_filter($items, static fn (string $item): bool => $item !== '');

    return array_values(array_unique($items));
}

/**
 * Returns a site setting value with a fallback.
 *
 * @param string $key Setting key.
 * @param string $fallback Value used when the setting is missing.
 * @return string Setting value.
 */
function pmGetSiteSetting(string $key, string $fallback = ''): string
{
    try {
        $stmt = pmDb()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $value = $stmt->fetchColumn();

        return is_string($value) && $value !== '' ? $value : $fallback;
    } catch (Throwable) {
        return $fallback;
    }
}

/**
 * Stores a site setting value.
 *
 * @param string $key Setting key.
 * @param string $value Setting value.
 * @return void
 */
function pmSetSiteSetting(string $key, string $value): void
{
    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO site_settings (setting_key, setting_value, updated_at)
        VALUES (:key, :value, CURRENT_TIMESTAMP)
        ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = CURRENT_TIMESTAMP
    SQL);
    $stmt->execute([':key' => $key, ':value' => $value]);
}

/**
 * Returns the default public module configuration for a clean install.
 *
 * Site Management and database seeding both use this shape so the restore
 * action returns the site to the same public-navigation state as first run.
 *
 * @return array<string, array{label:string,enabled:int,public:int,order:int}>
 */
function pmDefaultSiteModules(): array
{
    return [
        'portfolio' => ['label' => 'Portfolio', 'enabled' => 1, 'public' => 1, 'order' => 10],
        'blog' => ['label' => 'Blog', 'enabled' => 1, 'public' => 1, 'order' => 20],
        'contact' => ['label' => 'Contact Me', 'enabled' => 1, 'public' => 1, 'order' => 30],
        'qualifications' => ['label' => 'Qualifications', 'enabled' => 1, 'public' => 1, 'order' => 40],
        'cv' => ['label' => 'CV Download', 'enabled' => 1, 'public' => 1, 'order' => 50],
    ];
}

/**
 * Returns the default Portfolio Manager colour variables.
 *
 * These values match the original Portfolio Manager colour identity and are
 * reused by Site Management, the restore-defaults action, and the live preview.
 *
 * @return array<string, string> CSS variable names mapped to default colour values.
 */
function pmDefaultThemeVariables(): array
{
    return [
        '--prime-highlight-color' => '#FEFFFE',
        '--prime-color1' => '#E9EBF8',
        '--prime-color2' => '#B4B8C5',
        '--prime-color3' => '#a789a0',
        '--second-highlight-color' => '#FEFFFE',
        '--second-color1' => '#da639f',
        '--second-color2' => '#726471',
        '--third-color1' => '#181416',
        '--header-background-color' => '#181416',
        '--page-gradient-start' => '#181416',
        '--page-gradient-end' => '#da639f',
        '--window-gradient-start' => '#181416',
        '--window-gradient-end' => '#2a2028',
        '--border-color' => '#da639f',
        '--border-accent-soft' => '#da639f',
        '--danger-color' => '#c75072',
    ];
}


/**
 * Returns the default editable home page copy.
 *
 * Site Management uses these values for new installs and for the restore
 * defaults action so the public home page can always be reset to the original
 * Portfolio Manager wording.
 *
 * @return array{subheading:string,body:string} Default home page copy.
 */
function pmDefaultHomeContent(): array
{
    return [
        'subheading' => 'You can use this to help set up and share your portfolio.',
        'body' => 'View your current portfolio through the Portfolio link, or log in to manage projects, blog posts, qualifications, users, site settings, and CV builds.',
    ];
}

/**
 * Returns the default editable Contact Me page content.
 *
 * The Contact Me page uses a short information panel plus up to three optional
 * callout boxes. Keeping these defaults in one helper lets Site Management,
 * restore-to-default handling, and the public Contact Me page use the same
 * fallback values.
 *
 * @return array{heading:string,body:string,cards:array<int,array{label:string,text:string,enabled:int}>}
 */
function pmDefaultContactContent(): array
{
    return [
        'heading' => 'Let’s Work Together',
        'body' => 'Use this form for freelance development, contract work, collaboration opportunities, job offers, project discussions, or general professional enquiries. Portfolio Manager is designed to showcase work, technical ability, experience, and current project activity. A clear message with useful context will help the portfolio owner respond properly.',
        'cards' => [
            1 => ['label' => 'Get in touch for', 'text' => 'Contracts, freelance work, and collaborations', 'enabled' => 1],
            2 => ['label' => 'Also open to', 'text' => 'Job opportunities and professional discussions', 'enabled' => 1],
            3 => ['label' => 'Best approach', 'text' => 'Send a clear message with your opportunity or enquiry', 'enabled' => 1],
        ],
    ];
}


/**
 * Restores site identity, public modules, and colour settings to defaults.
 *
 * The dynamic theme settings are deleted rather than overwritten so the static
 * CSS file becomes the source of truth again after the restore action.
 *
 * @return void
 */
function pmRestoreDefaultSiteManagementSettings(): void
{
    pmSetSiteSetting('site_title', PM_APP_NAME);

    $homeDefaults = pmDefaultHomeContent();
    pmSetSiteSetting('home_subheading', $homeDefaults['subheading']);
    pmSetSiteSetting('home_body_text', $homeDefaults['body']);

    $contactDefaults = pmDefaultContactContent();
    pmSetSiteSetting('contact_heading', $contactDefaults['heading']);
    pmSetSiteSetting('contact_body', $contactDefaults['body']);

    foreach ($contactDefaults['cards'] as $cardIndex => $card) {
        pmSetSiteSetting('contact_card_' . $cardIndex . '_label', $card['label']);
        pmSetSiteSetting('contact_card_' . $cardIndex . '_text', $card['text']);
        pmSetSiteSetting('contact_card_' . $cardIndex . '_enabled', (string) $card['enabled']);
    }

    $moduleStmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO site_modules (module_key, module_label, is_enabled, is_public, display_order, updated_at)
        VALUES (:key, :label, :enabled, :public, :display_order, CURRENT_TIMESTAMP)
        ON CONFLICT(module_key) DO UPDATE SET
            module_label = excluded.module_label,
            is_enabled = excluded.is_enabled,
            is_public = excluded.is_public,
            display_order = excluded.display_order,
            updated_at = CURRENT_TIMESTAMP
    SQL);

    foreach (pmDefaultSiteModules() as $key => $module) {
        $moduleStmt->execute([
            ':key' => $key,
            ':label' => $module['label'],
            ':enabled' => $module['enabled'],
            ':public' => $module['public'],
            ':display_order' => $module['order'],
        ]);
    }

    pmDb()->exec('DELETE FROM theme_settings');

    // Restore social profile rows to the shipped defaults without keeping old profile URLs.
    try {
        pmDb()->exec('DELETE FROM social_links');
        pmSeedDefaultSocialLinks();
    } catch (Throwable) {
        // Social links are optional during early database setup.
    }
}

/**
 * Returns the configured public site title.
 *
 * @return string Site title.
 */
function pmAppName(): string
{
    return pmGetSiteSetting('site_title', pmAppNameFallback());
}

/**
 * Returns the editable subheading shown underneath the home page title.
 *
 * @return string Home page subheading.
 */
function pmHomeSubheading(): string
{
    $defaults = pmDefaultHomeContent();

    return pmGetSiteSetting('home_subheading', $defaults['subheading']);
}

/**
 * Returns the editable body text shown below the home page subheading.
 *
 * @return string Home page body text.
 */
function pmHomeBodyText(): string
{
    $defaults = pmDefaultHomeContent();

    return pmGetSiteSetting('home_body_text', $defaults['body']);
}


/**
 * Returns the editable Contact Me information-panel heading.
 *
 * @return string Contact panel heading.
 */
function pmContactHeading(): string
{
    $defaults = pmDefaultContactContent();

    return pmGetSiteSetting('contact_heading', $defaults['heading']);
}

/**
 * Returns the editable Contact Me information-panel body text.
 *
 * @return string Contact panel body text.
 */
function pmContactBody(): string
{
    $defaults = pmDefaultContactContent();

    return pmGetSiteSetting('contact_body', $defaults['body']);
}

/**
 * Returns up to three editable Contact Me callout cards.
 *
 * Disabled cards are filtered out on the public page but remain editable in
 * Site Management. Each card is stored as site_settings rows so the Contact Me
 * page does not need its own table for simple copy management.
 *
 * @param bool $includeDisabled Whether disabled cards should be returned.
 * @return array<int, array{index:int,label:string,text:string,enabled:int}>
 */
function pmContactCards(bool $includeDisabled = false): array
{
    $defaults = pmDefaultContactContent();
    $cards = [];

    for ($index = 1; $index <= 3; $index++) {
        $fallback = $defaults['cards'][$index] ?? ['label' => '', 'text' => '', 'enabled' => 0];
        $enabled = pmGetSiteSetting('contact_card_' . $index . '_enabled', (string) $fallback['enabled']) === '1' ? 1 : 0;
        $label = pmGetSiteSetting('contact_card_' . $index . '_label', $fallback['label']);
        $text = pmGetSiteSetting('contact_card_' . $index . '_text', $fallback['text']);

        if (!$includeDisabled && ($enabled !== 1 || ($label === '' && $text === ''))) {
            continue;
        }

        $cards[$index] = [
            'index' => $index,
            'label' => $label,
            'text' => $text,
            'enabled' => $enabled,
        ];
    }

    return $cards;
}

/**
 * Returns the path to the project JSON file.
 *
 * @return string Absolute path.
 */
function pmProjectJsonPath(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'projects.json';
}

/**
 * Reads projects from the JSON project store.
 *
 * @return array<int, array<string, mixed>> Project records.
 */
function pmReadProjects(): array
{
    $jsonFile = pmProjectJsonPath();
    $raw = is_file($jsonFile) ? file_get_contents($jsonFile) : '[]';
    $data = json_decode(is_string($raw) ? $raw : '[]', true);

    return is_array($data) ? array_values($data) : [];
}

/**
 * Writes projects to the JSON project store with an exclusive lock.
 *
 * @param array<int, array<string, mixed>> $projects Project records.
 * @return bool True when written successfully.
 */
function pmWriteProjects(array $projects): bool
{
    $jsonFile = pmProjectJsonPath();
    $jsonDir = dirname($jsonFile);

    if (!is_dir($jsonDir) && !mkdir($jsonDir, 0750, true)) {
        return false;
    }

    $encoded = json_encode(array_values($projects), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return is_string($encoded) && file_put_contents($jsonFile, $encoded, LOCK_EX) !== false;
}

/**
 * Returns enabled public modules from the database.
 *
 * @return array<string, array<string, mixed>> Module map indexed by module key.
 */
function pmEnabledPublicModules(): array
{
    try {
        $rows = pmDb()->query('SELECT * FROM site_modules WHERE is_enabled = 1 AND is_public = 1 ORDER BY display_order ASC')->fetchAll();
        $modules = [];

        foreach ($rows as $row) {
            $modules[(string) $row['module_key']] = $row;
        }

        return $modules;
    } catch (Throwable) {
        return [
            'portfolio' => ['module_label' => 'Portfolio'],
            'blog' => ['module_label' => 'Blog'],
            'contact' => ['module_label' => 'Contact Me'],
            'qualifications' => ['module_label' => 'Qualifications'],
            'cv' => ['module_label' => 'CV Download'],
        ];
    }
}

/**
 * Checks whether a public module is enabled and visible.
 *
 * @param string $moduleKey Module key.
 * @return bool True when public access is allowed.
 */
function pmIsPublicModuleEnabled(string $moduleKey): bool
{
    return array_key_exists($moduleKey, pmEnabledPublicModules());
}

/**
 * Returns every valid routed page.
 *
 * @return array<string, string> Map of route key to browser title.
 */
function pmValidPages(): array
{
    return [
        'home' => pmPageTitle('Home'),
        'portfolio' => pmPageTitle('Portfolio'),
        'blog' => pmPageTitle('Blog'),
        'blog-post' => pmPageTitle('Blog Post'),
        'contactme' => pmPageTitle('Contact Me'),
        'qualifications' => pmPageTitle('Qualifications'),
        'cv-download' => pmPageTitle('CV Download'),
        'adminlogonportal' => pmPageTitle('Admin Login'),
        'password-reset' => pmPageTitle('Password Reset'),
        'manage-project' => pmPageTitle('Manage Projects'),
        'manage-blog' => pmPageTitle('Manage Blog'),
        'manage-contact' => pmPageTitle('Contact Inbox'),
        'manage-qualifications' => pmPageTitle('Manage Qualifications'),
        'manage-cv' => pmPageTitle('CV Builder'),
        'site-management' => pmPageTitle('Site Management'),
        'user-management' => pmPageTitle('User Management'),
        '404' => pmPageTitle('404 - Page Not Found'),
    ];
}

/**
 * Returns admin-only pages and the permission required to view each page.
 *
 * @return array<string, string> Page-to-permission map.
 */
function pmAdminOnlyPages(): array
{
    return [
        'manage-project' => 'can_manage_projects',
        'manage-blog' => 'can_manage_blog',
        'manage-contact' => 'can_manage_contact',
        'manage-qualifications' => 'can_manage_qualifications',
        'manage-cv' => 'can_manage_cv',
        'site-management' => 'can_manage_site_settings',
        'user-management' => 'can_manage_users',
    ];
}

/**
 * Returns the module key that controls each public page.
 *
 * @return array<string, string> Page-to-module map.
 */
function pmPageModuleMap(): array
{
    return [
        'portfolio' => 'portfolio',
        'blog' => 'blog',
        'blog-post' => 'blog',
        'contactme' => 'contact',
        'qualifications' => 'qualifications',
        'cv-download' => 'cv',
    ];
}

/**
 * Normalises a raw route key into a safe page name.
 *
 * @param string|null $rawPage Raw page value.
 * @return string Safe page key.
 */
function pmNormalisePageName(?string $rawPage): string
{
    $page = trim((string) ($rawPage ?? PM_DEFAULT_PAGE));
    $page = preg_replace('/\.php$/i', '', $page) ?? PM_DEFAULT_PAGE;
    $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page) ?? PM_DEFAULT_PAGE;

    return $page !== '' ? $page : PM_DEFAULT_PAGE;
}

/**
 * Resolves a requested page into an includeable route.
 *
 * @param string|null $rawPage Raw page query value.
 * @param bool $isLoggedIn Whether the current visitor is logged in.
 * @return array{page:string,title:string,is404:bool,requested:string,redirect:?string}
 */
function pmResolvePage(?string $rawPage, bool $isLoggedIn): array
{
    $requested = trim((string) ($rawPage ?? PM_DEFAULT_PAGE));
    $page = pmNormalisePageName($rawPage);
    $validPages = pmValidPages();
    $adminPages = pmAdminOnlyPages();
    $moduleMap = pmPageModuleMap();
    $isKnownPage = array_key_exists($page, $validPages);

    if (isset($moduleMap[$page]) && !pmIsPublicModuleEnabled($moduleMap[$page]) && !$isLoggedIn) {
        $isKnownPage = false;
    }

    if (isset($adminPages[$page]) && (!$isLoggedIn || !pmHasPermission($adminPages[$page]))) {
        $isKnownPage = false;
    }

    if (!$isKnownPage) {
        return [
            'page' => '404',
            'title' => $validPages['404'],
            'is404' => true,
            'requested' => $requested,
            'redirect' => null,
        ];
    }

    return [
        'page' => $page,
        'title' => $validPages[$page],
        'is404' => false,
        'requested' => $requested,
        'redirect' => null,
    ];
}

/**
 * Returns public navigation links based on enabled modules.
 *
 * @return array<int, array{label:string,page:string}> Public nav links.
 */
function pmPublicNavigationLinks(): array
{
    $links = [
        ['label' => 'Home', 'page' => 'home'],
    ];

    $moduleRoutes = [
        'portfolio' => ['label' => 'Portfolio', 'page' => 'portfolio'],
        'blog' => ['label' => 'Blog', 'page' => 'blog'],
        'contact' => ['label' => 'Contact Me', 'page' => 'contactme'],
        'qualifications' => ['label' => 'Qualifications', 'page' => 'qualifications'],
        'cv' => ['label' => 'CV', 'page' => 'cv-download'],
    ];

    foreach (pmEnabledPublicModules() as $moduleKey => $module) {
        if (isset($moduleRoutes[$moduleKey])) {
            $link = $moduleRoutes[$moduleKey];
            $link['label'] = (string) ($module['module_label'] ?? $link['label']);
            $links[] = $link;
        }
    }

    return $links;
}

/**
 * Returns admin navigation links based on the current user's permissions.
 *
 * @return array<int, array{label:string,page:string}> Admin nav links.
 */
function pmAdminNavigationLinks(): array
{
    $links = [];
    $candidates = [
        ['label' => 'Manage Projects', 'page' => 'manage-project', 'permission' => 'can_manage_projects'],
        ['label' => 'Manage Blog', 'page' => 'manage-blog', 'permission' => 'can_manage_blog'],
        ['label' => 'Contact Inbox', 'page' => 'manage-contact', 'permission' => 'can_manage_contact'],
        ['label' => 'Qualifications', 'page' => 'manage-qualifications', 'permission' => 'can_manage_qualifications'],
        ['label' => 'CV Builder', 'page' => 'manage-cv', 'permission' => 'can_manage_cv'],
        ['label' => 'Users', 'page' => 'user-management', 'permission' => 'can_manage_users'],
        ['label' => 'Site Management', 'page' => 'site-management', 'permission' => 'can_manage_site_settings'],
    ];

    foreach ($candidates as $candidate) {
        if (pmHasPermission($candidate['permission'])) {
            $links[] = ['label' => $candidate['label'], 'page' => $candidate['page']];
        }
    }

    $links[] = ['label' => 'Logout', 'page' => 'logout'];

    return $links;
}

/**
 * Migrates legacy custom technology records into the unified tech_items table.
 *
 * Earlier staged builds stored user-created technologies in custom_tech_items.
 * Stage Tech 1 introduced tech_items as the long-term single catalogue source.
 * This bridge keeps any existing custom rows visible while later UI stages move
 * the add/edit/delete forms to tech_items directly.
 *
 * @return void
 */
function pmMigrateLegacyCustomTechItems(): void
{
    try {
        $rows = pmDb()->query('SELECT tech_key, label, category, icon_path, created_by_user_id FROM custom_tech_items')->fetchAll();
    } catch (Throwable) {
        return;
    }

    if ($rows === []) {
        return;
    }

    $insert = pmDb()->prepare(<<<'SQL'
        INSERT OR IGNORE INTO tech_items (
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
            9000,
            :created_by_user_id,
            CURRENT_TIMESTAMP
        )
    SQL);

    foreach ($rows as $row) {
        $category = (string) ($row['category'] ?? 'misc');

        if (!array_key_exists($category, pmTechCategories())) {
            $category = 'misc';
        }

        $insert->execute([
            ':tech_key' => pmTechKeyFromLabel((string) ($row['tech_key'] ?? '')),
            ':label' => (string) ($row['label'] ?? ''),
            ':category' => $category,
            ':icon_path' => (string) ($row['icon_path'] ?? ''),
            ':created_by_user_id' => $row['created_by_user_id'] ?? null,
        ]);
    }
}

/**
 * Returns the active technology catalogue from SQLite.
 *
 * The tech_items table is now the single runtime source used by PHP helpers.
 * Default technologies are seeded into this table during database bootstrap,
 * and Site Management edits these rows directly instead of duplicating
 * catalogue arrays across PHP and JavaScript files.
 *
 * @return array<string, array{label:string,category:string,icon:string|null}> Active tech catalogue keyed by tech key.
 */
function pmTechCatalogue(): array
{
    try {
        pmMigrateLegacyCustomTechItems();

        $rows = pmDb()->query(<<<'SQL'
            SELECT tech_key, label, category, icon_path
            FROM tech_items
            WHERE is_active = 1
        SQL)->fetchAll();
    } catch (Throwable) {
        return pmDefaultTechCatalogue();
    }

    if ($rows === []) {
        return pmDefaultTechCatalogue();
    }

    $catalogue = [];

    foreach ($rows as $row) {
        $key = pmTechKeyFromLabel((string) ($row['tech_key'] ?? ''));

        if ($key === '' || $key === 'misc') {
            continue;
        }

        $category = (string) ($row['category'] ?? 'misc');

        if (!array_key_exists($category, pmTechCategories())) {
            $category = 'misc';
        }

        $catalogue[$key] = [
            'label' => (string) ($row['label'] ?? $key),
            'category' => $category,
            'icon' => (string) ($row['icon_path'] ?? '') !== '' ? (string) $row['icon_path'] : null,
        ];
    }

    return $catalogue !== [] ? pmSortTechCatalogue($catalogue) : pmDefaultTechCatalogue();
}

/**
 * Sorts technology catalogue records by configured category order and item label.
 *
 * The database remains the single source of truth, but sorting in PHP keeps the
 * same category ordering across Portfolio, Blog, Manage Projects, Manage Blog,
 * and Site Management while alphabetising the individual items inside each
 * category.
 *
 * @param array<string, array{label:string,category:string,icon:string|null}> $catalogue Raw catalogue rows.
 * @return array<string, array{label:string,category:string,icon:string|null}> Sorted catalogue rows.
 */
function pmSortTechCatalogue(array $catalogue): array
{
    uasort($catalogue, static function (array $left, array $right): int {
        $categoryOrder = array_flip(array_keys(pmTechCategories()));
        $leftCategory = (string) ($left['category'] ?? 'misc');
        $rightCategory = (string) ($right['category'] ?? 'misc');
        $categoryCompare = ($categoryOrder[$leftCategory] ?? PHP_INT_MAX) <=> ($categoryOrder[$rightCategory] ?? PHP_INT_MAX);

        if ($categoryCompare !== 0) {
            return $categoryCompare;
        }

        return strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
    });

    return $catalogue;
}

/**
 * Returns all technology rows for the Site Management catalogue editor.
 *
 * Unlike pmTechCatalogue(), this includes disabled rows and default rows so the
 * owner can hide, restore, relabel, recategorise, or replace icons without the
 * selector screens needing their own hardcoded catalogue.
 *
 * @return array<int, array<string, mixed>> Editable technology rows.
 */
function pmTechnologyRowsForManagement(): array
{
    try {
        pmMigrateLegacyCustomTechItems();

        $rows = pmDb()->query(<<<'SQL'
            SELECT
                id,
                tech_key,
                label,
                category,
                icon_path,
                is_default,
                is_active,
                display_order,
                created_by_user_id,
                created_at,
                updated_at
            FROM tech_items
        SQL)->fetchAll();
    } catch (Throwable) {
        return [];
    }

    usort($rows, static function (array $left, array $right): int {
        $categoryOrder = array_flip(array_keys(pmTechCategories()));
        $leftCategory = (string) ($left['category'] ?? 'misc');
        $rightCategory = (string) ($right['category'] ?? 'misc');
        $categoryCompare = ($categoryOrder[$leftCategory] ?? PHP_INT_MAX) <=> ($categoryOrder[$rightCategory] ?? PHP_INT_MAX);

        if ($categoryCompare !== 0) {
            return $categoryCompare;
        }

        return strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
    });

    return $rows;
}

/**
 * Returns custom/non-default technology rows for management screens.
 *
 * This helper exposes non-default active rows for any older templates that still
 * expect a custom-only list. New management screens should use
 * pmTechnologyRowsForManagement() so default and custom rows share one editor.
 *
 * @return array<int, array<string, mixed>> Custom technology records.
 */
function pmCustomTechRows(): array
{
    try {
        pmMigrateLegacyCustomTechItems();

        return pmDb()->query(<<<'SQL'
            SELECT
                id,
                tech_key,
                label,
                category,
                icon_path,
                created_by_user_id,
                created_at,
                updated_at
            FROM tech_items
            WHERE is_default = 0
              AND is_active = 1
            ORDER BY category ASC, label COLLATE NOCASE ASC
        SQL)->fetchAll();
    } catch (Throwable) {
        return [];
    }
}



/**
 * Returns the browser path for a technology icon value.
 *
 * Built-in icons are stored under src/icons. Custom uploaded icons are stored
 * under src/uploads/tech-icons and are saved in the database with an uploads/*
 * path so they can be distinguished safely.
 *
 * @param string|null $icon Icon filename or uploaded icon path.
 * @return string Browser-ready icon path.
 */
function pmTechIconPath(?string $icon): string
{
    $icon = trim((string) $icon);

    if ($icon === '') {
        return 'src/icons/unknown.svg';
    }

    if (str_starts_with($icon, 'uploads/')) {
        return 'src/' . $icon;
    }

    return 'src/icons/' . $icon;
}

/**
 * Returns the operating system and platform catalogue used by admin selectors.
 *
 * Keeping this list in PHP lets the Blog Manager render the same platform
 * choices that the Project Manager renders in JavaScript.
 *
 * @return array<string, array{label:string,icon:string}> Platform catalogue.
 */
function pmOsPlatformCatalogue(): array
{
    return [
        'web' => ['label' => 'Web', 'icon' => 'src/icons/os/web.png'],
        'windows' => ['label' => 'Windows', 'icon' => 'src/icons/os/windows.png'],
        'macos' => ['label' => 'macOS', 'icon' => 'src/icons/os/apple.png'],
        'linux' => ['label' => 'Linux', 'icon' => 'src/icons/os/linux.png'],
        'ios' => ['label' => 'iOS', 'icon' => 'src/icons/os/ios.png'],
        'android' => ['label' => 'Android', 'icon' => 'src/icons/os/android.svg'],
        'raspberry-pi' => ['label' => 'Raspberry Pi', 'icon' => 'src/icons/os/rpi.png'],
    ];
}

/**
 * Normalises a technology label into a catalogue key.
 *
 * @param string $label Technology label.
 * @return string Normalised key.
 */
function pmTechKeyFromLabel(string $label): string
{
    $key = strtolower(trim($label));
    $key = str_replace(['#', '+', '.', ' '], ['sharp', 'plus', '', ''], $key);
    $key = preg_replace('/[^a-z0-9_]/', '', $key) ?? '';

    return $key !== '' ? $key : 'misc';
}

/**
 * Converts old flat tech arrays into grouped tech arrays.
 *
 * @param mixed $tech Raw tech value from a project record.
 * @return array<string, array<int, string>> Grouped tech labels.
 */
function pmNormaliseProjectTech(mixed $tech): array
{
    $categories = array_fill_keys(array_keys(pmTechCategories()), []);
    $catalogue = pmTechCatalogue();

    if (is_array($tech) && array_is_list($tech)) {
        foreach ($tech as $label) {
            $label = pmString($label);
            $key = pmTechKeyFromLabel($label);
            $category = $catalogue[$key]['category'] ?? 'misc';
            $categories[$category][] = $catalogue[$key]['label'] ?? $label;
        }
    } elseif (is_array($tech)) {
        foreach ($categories as $category => $_) {
            $categories[$category] = pmStringList($tech[$category] ?? []);
        }
    }

    foreach ($categories as $category => $items) {
        $categories[$category] = array_values(array_unique(array_filter($items)));
    }

    return $categories;
}

/**
 * Returns all blog posts, optionally including drafts.
 *
 * @param bool $includeDrafts Whether drafts should be included.
 * @return array<int, array<string, mixed>> Blog posts.
 */
function pmGetBlogPosts(bool $includeDrafts = false): array
{
    $sql = $includeDrafts
        ? 'SELECT blog_posts.*, users.display_name, users.username FROM blog_posts LEFT JOIN users ON users.id = blog_posts.author_user_id ORDER BY COALESCE(blog_posts.published_at, blog_posts.created_at) DESC'
        : 'SELECT blog_posts.*, users.display_name, users.username FROM blog_posts LEFT JOIN users ON users.id = blog_posts.author_user_id WHERE blog_posts.is_published = 1 ORDER BY COALESCE(blog_posts.published_at, blog_posts.created_at) DESC';

    return pmDb()->query($sql)->fetchAll();
}

/**
 * Finds a published blog post by slug.
 *
 * @param string $slug Post slug.
 * @return array<string, mixed>|null Blog post or null.
 */
function pmFindBlogPostBySlug(string $slug): ?array
{
    $stmt = pmDb()->prepare(<<<'SQL'
        SELECT blog_posts.*, users.display_name, users.username
        FROM blog_posts
        LEFT JOIN users ON users.id = blog_posts.author_user_id
        WHERE blog_posts.slug = :slug AND blog_posts.is_published = 1
        LIMIT 1
    SQL);
    $stmt->execute([':slug' => $slug]);
    $post = $stmt->fetch();

    return is_array($post) ? $post : null;
}

/**
 * Creates a URL-friendly blog slug.
 *
 * @param string $title Source title.
 * @return string Slug.
 */
function pmSlugify(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'post-' . time();
}

/**
 * Returns visible qualifications ordered for display.
 *
 * @param bool $includeHidden Whether hidden entries should be included.
 * @return array<int, array<string, mixed>> Qualification rows.
 */
function pmGetQualifications(bool $includeHidden = false): array
{
    $sql = $includeHidden
        ? 'SELECT * FROM qualifications ORDER BY display_order ASC, obtained_date DESC, id DESC'
        : 'SELECT * FROM qualifications WHERE show_on_qualifications_page = 1 ORDER BY display_order ASC, obtained_date DESC, id DESC';

    return pmDb()->query($sql)->fetchAll();
}

/**
 * Returns the single stored CV profile row, creating it if required.
 *
 * @return array<string, mixed> CV profile.
 */
function pmGetCvProfile(): array
{
    pmDb()->exec('INSERT OR IGNORE INTO cv_profile (id) VALUES (1)');
    $profile = pmDb()->query('SELECT * FROM cv_profile WHERE id = 1')->fetch();

    return is_array($profile) ? $profile : [];
}

/**
 * Returns all CV jobs.
 *
 * @return array<int, array<string, mixed>> Job rows.
 */
function pmGetCvJobs(): array
{
    return pmDb()->query('SELECT * FROM cv_jobs ORDER BY display_order ASC, start_year DESC, id DESC')->fetchAll();
}

/**
 * Returns all CV skills.
 *
 * @return array<int, array<string, mixed>> Skill rows.
 */
function pmGetCvSkills(): array
{
    return pmDb()->query('SELECT * FROM cv_skills ORDER BY skill_group ASC, display_order ASC, skill_name ASC')->fetchAll();
}

/**
 * Returns all CV builds.
 *
 * @return array<int, array<string, mixed>> Build rows.
 */
function pmGetCvBuilds(): array
{
    return pmDb()->query('SELECT * FROM cv_builds ORDER BY is_public DESC, updated_at DESC, id DESC')->fetchAll();
}

/**
 * Returns the selected item IDs stored against one CV build.
 *
 * CV builds can target different roles, so each build may choose a different
 * set of jobs, skills, and qualifications. The helper returns IDs grouped by
 * item type so the builder page and renderer do not duplicate SQL parsing.
 *
 * @param int $buildId CV build ID.
 * @return array<string, array<int, int>> Selected item IDs grouped by type.
 */
function pmGetCvBuildItemIds(int $buildId): array
{
    $selected = [
        'job' => [],
        'skill' => [],
        'qualification' => [],
    ];

    if ($buildId <= 0) {
        return $selected;
    }

    $stmt = pmDb()->prepare('SELECT item_type, item_id FROM cv_build_items WHERE build_id = :build_id ORDER BY display_order ASC, id ASC');
    $stmt->execute([':build_id' => $buildId]);

    foreach ($stmt->fetchAll() as $row) {
        $type = (string) ($row['item_type'] ?? '');
        if (array_key_exists($type, $selected)) {
            $selected[$type][] = (int) $row['item_id'];
        }
    }

    return $selected;
}

/**
 * Filters rows to the item IDs selected for a specific CV build.
 *
 * If a build has no saved items for that type, the function falls back to all
 * rows so older builds created before item selection still render sensibly.
 *
 * @param array<int, array<string, mixed>> $rows Source rows.
 * @param array<int, int> $selectedIds Selected IDs for this item type.
 * @return array<int, array<string, mixed>> Filtered rows.
 */
function pmFilterCvRowsBySelection(array $rows, array $selectedIds): array
{
    if ($selectedIds === []) {
        return $rows;
    }

    $allowed = array_flip(array_map('intval', $selectedIds));

    return array_values(array_filter(
        $rows,
        static fn (array $row): bool => isset($allowed[(int) ($row['id'] ?? 0)])
    ));
}

/**
 * Finds the currently public CV build.
 *
 * @return array<string, mixed>|null Build row or null.
 */
function pmGetPublicCvBuild(): ?array
{
    $buildId = (int) pmGetSiteSetting('public_cv_build_id', '0');

    if ($buildId <= 0) {
        $buildId = (int) pmDb()->query('SELECT id FROM cv_builds WHERE is_public = 1 ORDER BY updated_at DESC LIMIT 1')->fetchColumn();
    }

    if ($buildId <= 0) {
        return null;
    }

    $stmt = pmDb()->prepare('SELECT * FROM cv_builds WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $buildId]);
    $build = $stmt->fetch();

    return is_array($build) ? $build : null;
}

/**
 * Renders a simple ATS-friendly CV HTML block.
 *
 * @param array<string, mixed>|null $build Optional CV build row.
 * @return string HTML CV content.
 */
function pmRenderCvHtml(?array $build): string
{
    $profile = pmGetCvProfile();
    $selectedItems = is_array($build) ? pmGetCvBuildItemIds((int) $build['id']) : ['job' => [], 'skill' => [], 'qualification' => []];
    $jobs = pmFilterCvRowsBySelection(pmGetCvJobs(), $selectedItems['job'] ?? []);
    $skills = pmFilterCvRowsBySelection(
        array_values(array_filter(pmGetCvSkills(), static fn (array $row): bool => (int) ($row['is_visible'] ?? 1) === 1)),
        $selectedItems['skill'] ?? []
    );
    $qualifications = pmFilterCvRowsBySelection(
        array_values(array_filter(pmGetQualifications(false), static fn (array $row): bool => (int) $row['available_for_cv'] === 1)),
        $selectedItems['qualification'] ?? []
    );
    $summary = $build !== null && trim((string) $build['tailored_summary']) !== '' ? (string) $build['tailored_summary'] : (string) ($profile['summary'] ?? '');

    ob_start();
    ?>
    <article class="cv-document">
        <header class="cv-header">
            <h1><?= pmEscape($profile['full_name'] ?: pmAppName()) ?></h1>
            <?php if (!empty($profile['headline'])): ?><p><?= pmEscape($profile['headline']) ?></p><?php endif; ?>
            <p><?= pmEscape(trim(($profile['email'] ?? '') . ' ' . ($profile['phone'] ?? '') . ' ' . ($profile['location'] ?? ''))) ?></p>
        </header>
        <?php if ($summary !== ''): ?>
            <section><h2>Professional Summary</h2><p><?= nl2br(pmEscape($summary)) ?></p></section>
        <?php endif; ?>
        <?php if ($skills !== []): ?>
            <section><h2>Skills</h2><p><?= pmEscape(implode(', ', array_map(static fn (array $skill): string => (string) $skill['skill_name'], $skills))) ?></p></section>
        <?php endif; ?>
        <?php if ($jobs !== []): ?>
            <section><h2>Work Experience</h2>
                <?php foreach ($jobs as $job): ?>
                    <h3><?= pmEscape($job['role_title']) ?> — <?= pmEscape($job['employer_name']) ?></h3>
                    <p><?= pmEscape(trim(($job['start_month'] ? $job['start_month'] . ' ' : '') . $job['start_year'])) ?> – <?= (int) $job['is_current'] === 1 ? 'Present' : pmEscape(trim(($job['end_month'] ? $job['end_month'] . ' ' : '') . $job['end_year'])) ?></p>
                    <?php if (!empty($job['summary'])): ?><p><?= nl2br(pmEscape($job['summary'])) ?></p><?php endif; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
        <?php if ($qualifications !== []): ?>
            <section><h2>Qualifications</h2><ul><?php foreach ($qualifications as $qualification): ?><li><?= pmEscape($qualification['title']) ?><?= $qualification['provider'] ? ' — ' . pmEscape($qualification['provider']) : '' ?></li><?php endforeach; ?></ul></section>
        <?php endif; ?>
    </article>
    <?php
    return (string) ob_get_clean();
}


/**
 * Returns the built-in social profile platforms offered by Site Management.
 *
 * Repository providers, email, and generic website links are intentionally excluded so
 * this section remains focused on social/media profiles rather than project source
 * links or contact-form alternatives.
 *
 * @return array<string, array{label:string,icon:string,order:int}>
 */
function pmDefaultSocialPlatforms(): array
{
    return [
        'linkedin' => ['label' => 'LinkedIn', 'icon' => 'socials/LinkedIn.svg', 'order' => 10],
        'youtube' => ['label' => 'YouTube', 'icon' => 'socials/YouTube.svg', 'order' => 20],
        'x-twitter' => ['label' => 'X / Twitter', 'icon' => 'socials/X.svg', 'order' => 30],
        'facebook' => ['label' => 'Facebook', 'icon' => 'socials/Facebook.svg', 'order' => 40],
        'instagram' => ['label' => 'Instagram', 'icon' => 'socials/Instagram.svg', 'order' => 50],
        'tiktok' => ['label' => 'TikTok', 'icon' => 'socials/TikTok.svg', 'order' => 60],
        'mastodon' => ['label' => 'Mastodon', 'icon' => 'socials/Mastodon.svg', 'order' => 70],
        'bluesky' => ['label' => 'Bluesky', 'icon' => 'socials/Bluesky.svg', 'order' => 80],
        'threads' => ['label' => 'Threads', 'icon' => 'socials/Threads.svg', 'order' => 90],
        'discord' => ['label' => 'Discord', 'icon' => 'socials/Discord.svg', 'order' => 100],
        'twitch' => ['label' => 'Twitch', 'icon' => 'socials/Twitch.svg', 'order' => 110],
        'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'socials/WhatsApp.svg', 'order' => 120],
        'telegram' => ['label' => 'Telegram', 'icon' => 'socials/Telegram.svg', 'order' => 130],
    ];
}

/**
 * Normalises a social platform label into a stable database key.
 *
 * @param string $label Platform label entered by the user.
 * @return string Safe key for URLs, CSS hooks, and database rows.
 */
function pmSocialKeyFromLabel(string $label): string
{
    $key = strtolower(trim($label));
    $key = str_replace(['/', '&', '+'], ['-', 'and', 'plus'], $key);
    $key = preg_replace('/[^a-z0-9-]+/', '-', $key) ?? '';
    $key = trim($key, '-');

    return $key !== '' ? $key : 'social';
}

/**
 * Ensures the default social profile rows exist in the database.
 *
 * Empty default rows are inactive until the owner enters a profile URL and
 * chooses where the icon should be displayed.
 *
 * @return void
 */
function pmSeedDefaultSocialLinks(): void
{
    try {
        $stmt = pmDb()->prepare(<<<'SQL'
            INSERT INTO social_links (platform_key, platform_label, icon_path, icon_filter, is_default, is_active, display_order, updated_at)
            VALUES (:key, :label, :icon_path, 'white', 1, 1, :display_order, CURRENT_TIMESTAMP)
            ON CONFLICT(platform_key) DO UPDATE SET
                platform_label = CASE WHEN social_links.platform_label = '' THEN excluded.platform_label ELSE social_links.platform_label END,
                icon_path = CASE WHEN social_links.icon_path = '' THEN excluded.icon_path ELSE social_links.icon_path END,
                icon_filter = CASE WHEN social_links.icon_filter NOT IN ('white', 'black') THEN 'white' ELSE social_links.icon_filter END,
                is_default = 1,
                display_order = CASE WHEN social_links.display_order = 0 THEN excluded.display_order ELSE social_links.display_order END,
                updated_at = CURRENT_TIMESTAMP
        SQL);

        foreach (pmDefaultSocialPlatforms() as $key => $platform) {
            $stmt->execute([
                ':key' => $key,
                ':label' => $platform['label'],
                ':icon_path' => 'icons/' . $platform['icon'],
                ':display_order' => $platform['order'],
            ]);
        }

        // Migrate bundled default social icons into src/icons/socials while leaving
        // user-uploaded custom icons untouched. Older development builds stored
        // bundled social paths directly under src/icons.
        $moveDefaultIcon = pmDb()->prepare('UPDATE social_links SET icon_path = :new_icon, updated_at = CURRENT_TIMESTAMP WHERE platform_key = :key AND icon_path = :old_icon');

        foreach (pmDefaultSocialPlatforms() as $key => $platform) {
            $filename = basename($platform['icon']);
            $moveDefaultIcon->execute([
                ':key' => $key,
                ':old_icon' => 'icons/' . $filename,
                ':new_icon' => 'icons/' . $platform['icon'],
            ]);
        }
    } catch (Throwable) {
        // Database initialisation can fail gracefully on first-run environments.
    }
}

/**
 * Returns every social link row for Site Management.
 *
 * @return array<int, array<string, mixed>> Social profile rows.
 */
function pmAllSocialLinks(): array
{
    pmSeedDefaultSocialLinks();

    try {
        return pmDb()->query('SELECT * FROM social_links ORDER BY display_order ASC, platform_label COLLATE NOCASE ASC')->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Returns active social links for a specific public display location.
 *
 * @param string $location Either footer or contact.
 * @return array<int, array<string, mixed>> Visible social profile rows.
 */
function pmVisibleSocialLinks(string $location): array
{
        $column = $location === 'contact' ? 'show_on_contact_page' : 'show_in_footer';
        pmSeedDefaultSocialLinks();

    try {
        $stmt = pmDb()->prepare("SELECT * FROM social_links WHERE is_active = 1 AND {$column} = 1 AND profile_url != '' ORDER BY display_order ASC, platform_label COLLATE NOCASE ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Returns social links configured for the footer.
 *
 * @return array<int, array<string, mixed>> Footer social rows.
 */
function pmFooterSocialLinks(): array
{
    return pmVisibleSocialLinks('footer');
}

/**
 * Returns social links configured for the Contact Me page.
 *
 * @return array<int, array<string, mixed>> Contact page social rows.
 */
function pmContactSocialLinks(): array
{
    return pmVisibleSocialLinks('contact');
}

/**
 * Converts a social profile URL into a safe public href.
 *
 * @param string $url Raw stored URL.
 * @return string Safe href or an empty string when invalid.
 */
function pmNormaliseSocialUrl(string $url): string
{
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    $candidate = preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $url) === 1 ? $url : 'https://' . $url;
    $parts = parse_url($candidate);

    if (!is_array($parts) || !isset($parts['scheme']) || !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
        return '';
    }

    return $candidate;
}

/**
 * Returns the browser path for a social icon.
 *
 * Bundled social icons live in src/icons/socials. The unknown fallback keeps
 * using the existing src/icons/unknown.svg path shared by the rest of the app.
 *
 * @param string|null $iconPath Stored icon path or filename.
 * @return string Browser-ready icon path.
 */
function pmSocialIconPath(?string $iconPath): string
{
    $iconPath = trim((string) $iconPath);

    if ($iconPath === '') {
        return 'src/icons/unknown.svg';
    }

    if (str_starts_with($iconPath, 'icons/')) {
        return 'src/' . $iconPath;
    }

    // Bare filenames are treated as bundled social icons. Custom uploads are
    // saved with an icons/socials/... path by the social link handler.
    return 'src/icons/socials/' . basename($iconPath);
}

/**
 * Builds clickable social icon links for the footer or Contact Me page.
 *
 * @param array<int, array<string, mixed>> $links Social link rows.
 * @param string $className Class applied to each anchor.
 * @return string Rendered icon anchors.
 */
function pmRenderSocialIconLinks(array $links, string $className = 'social-icon-link'): string
{
    $html = '';

    foreach ($links as $link) {
        $href = pmNormaliseSocialUrl((string) ($link['profile_url'] ?? ''));

        if ($href === '') {
            continue;
        }

        $label = (string) ($link['platform_label'] ?? 'Social profile');
        $icon = pmSocialIconPath((string) ($link['icon_path'] ?? ''));
        $filter = (string) ($link['icon_filter'] ?? 'white') === 'black' ? 'black' : 'white';
        $classes = trim($className . ' social-icon-filter-' . $filter);

        $html .= sprintf(
            '<a class="%s" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s" title="%s"><img src="%s" alt=""></a>',
            pmEscape($classes),
            pmEscape($href),
            pmEscape($label),
            pmEscape($label),
            pmEscape($icon)
        );
    }

    return $html;
}
