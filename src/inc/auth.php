<?php
/**
 * Portfolio Manager authentication, database, permission, CSRF, and token helpers.
 *
 * The functions in this file deliberately use the pm* prefix so the public repo
 * stays generic and does not inherit private project naming.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tech-defaults.php';

/**
 * Returns whether the current request is using HTTPS.
 *
 * Cookie security flags use this helper so local HTTP development remains easy
 * while production HTTPS gets stricter browser protection automatically.
 *
 * @return bool True when HTTPS appears to be active.
 */
function pmCookieSecure(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

/**
 * Starts the application session using hardened cookie settings.
 *
 * The function is idempotent, so it is safe for included files and handlers to
 * call it without checking session_status() first.
 *
 * @return void
 */
function pmStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(PM_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => pmCookieSecure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Returns the configured SQLite database path.
 *
 * Deployments can use PORTFOLIO_MANAGER_DB_PATH to place the database outside
 * the public web root. The fallback location is protected by src/inc/db/.htaccess.
 *
 * @return string Absolute database path.
 */
function pmDatabasePath(): string
{
    $envPath = getenv('PORTFOLIO_MANAGER_DB_PATH');

    if (is_string($envPath) && trim($envPath) !== '') {
        return $envPath;
    }

    return __DIR__ . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'portfolio-manager.sqlite';
}

/**
 * Opens and returns the shared PDO SQLite connection.
 *
 * The database schema is created and upgraded on first access so cloned installs
 * can run without manual SQL setup.
 *
 * @return PDO Active PDO connection.
 */
function pmDb(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('The PHP pdo_sqlite extension is not enabled.');
    }

    $dbPath = pmDatabasePath();
    $dbDir = dirname($dbPath);

    if (!is_dir($dbDir) && !mkdir($dbDir, 0750, true)) {
        throw new RuntimeException('Could not create SQLite database directory: ' . $dbDir);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    pmInitialiseDatabase($pdo);

    return $pdo;
}

/**
 * Creates every database table needed by the staged review build.
 *
 * This includes current migration stages: auth, remember-me, password reset,
 * permissions, module visibility, theme settings, blog, contact, qualifications,
 * and CV builder tables.
 *
 * @param PDO $pdo Active database connection.
 * @return void
 */
function pmInitialiseDatabase(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE COLLATE NOCASE,
            email TEXT NOT NULL DEFAULT '',
            recovery_email TEXT NOT NULL DEFAULT '',
            display_name TEXT NOT NULL DEFAULT '',
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'admin',
            is_primary_owner INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            must_change_password INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login_at TEXT
        )
    SQL);

    // These ALTER helpers keep older Stage 1 databases compatible with the full staged build.
    pmEnsureColumn($pdo, 'users', 'email', "TEXT NOT NULL DEFAULT ''");
    pmEnsureColumn($pdo, 'users', 'recovery_email', "TEXT NOT NULL DEFAULT ''");
    pmEnsureColumn($pdo, 'users', 'display_name', "TEXT NOT NULL DEFAULT ''");
    pmEnsureColumn($pdo, 'users', 'role', "TEXT NOT NULL DEFAULT 'admin'");
    pmEnsureColumn($pdo, 'users', 'is_primary_owner', 'INTEGER NOT NULL DEFAULT 0');
    pmEnsureColumn($pdo, 'users', 'is_active', 'INTEGER NOT NULL DEFAULT 1');
    pmEnsureColumn($pdo, 'users', 'must_change_password', 'INTEGER NOT NULL DEFAULT 0');

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS remember_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            selector TEXT NOT NULL UNIQUE,
            token_hash TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_used_at TEXT,
            user_agent TEXT NOT NULL DEFAULT '',
            ip_address TEXT NOT NULL DEFAULT '',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            selector TEXT NOT NULL UNIQUE,
            token_hash TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            used_at TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            request_ip TEXT NOT NULL DEFAULT '',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS user_permissions (
            user_id INTEGER NOT NULL,
            permission_key TEXT NOT NULL,
            can_access INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY (user_id, permission_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS site_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NOT NULL DEFAULT '',
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS site_modules (
            module_key TEXT PRIMARY KEY,
            module_label TEXT NOT NULL,
            is_enabled INTEGER NOT NULL DEFAULT 1,
            is_public INTEGER NOT NULL DEFAULT 1,
            display_order INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS theme_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NOT NULL DEFAULT '',
            updated_by_user_id INTEGER,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS social_links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            platform_key TEXT NOT NULL UNIQUE COLLATE NOCASE,
            platform_label TEXT NOT NULL,
            profile_url TEXT NOT NULL DEFAULT '',
            icon_path TEXT NOT NULL DEFAULT '',
            show_in_footer INTEGER NOT NULL DEFAULT 0,
            show_on_contact_page INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            display_order INTEGER NOT NULL DEFAULT 0,
            is_default INTEGER NOT NULL DEFAULT 0,
            icon_filter TEXT NOT NULL DEFAULT 'white',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    pmEnsureColumn($pdo, 'social_links', 'icon_path', "TEXT NOT NULL DEFAULT ''");
    pmEnsureColumn($pdo, 'social_links', 'show_in_footer', 'INTEGER NOT NULL DEFAULT 0');
    pmEnsureColumn($pdo, 'social_links', 'show_on_contact_page', 'INTEGER NOT NULL DEFAULT 0');
    pmEnsureColumn($pdo, 'social_links', 'is_active', 'INTEGER NOT NULL DEFAULT 1');
    pmEnsureColumn($pdo, 'social_links', 'display_order', 'INTEGER NOT NULL DEFAULT 0');
    pmEnsureColumn($pdo, 'social_links', 'is_default', 'INTEGER NOT NULL DEFAULT 0');
    pmEnsureColumn($pdo, 'social_links', 'icon_filter', "TEXT NOT NULL DEFAULT 'white'");

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS custom_tech_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tech_key TEXT NOT NULL UNIQUE COLLATE NOCASE,
            label TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'misc',
            icon_path TEXT NOT NULL DEFAULT '',
            created_by_user_id INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS tech_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tech_key TEXT NOT NULL UNIQUE COLLATE NOCASE,
            label TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'misc',
            icon_path TEXT NOT NULL DEFAULT '',
            is_default INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            display_order INTEGER NOT NULL DEFAULT 0,
            created_by_user_id INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    SQL);

    // Migration helpers keep any early custom catalogue database compatible
    // with the unified tech_items table used by the v1.0.0 tech refactor.
    pmEnsureColumn($pdo, 'tech_items', 'icon_path', "TEXT NOT NULL DEFAULT ''");
    pmEnsureColumn($pdo, 'tech_items', 'is_default', 'INTEGER NOT NULL DEFAULT 0');
    pmEnsureColumn($pdo, 'tech_items', 'is_active', 'INTEGER NOT NULL DEFAULT 1');
    pmEnsureColumn($pdo, 'tech_items', 'display_order', 'INTEGER NOT NULL DEFAULT 0');
    pmEnsureColumn($pdo, 'tech_items', 'created_by_user_id', 'INTEGER');

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS blog_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE COLLATE NOCASE,
            excerpt TEXT NOT NULL DEFAULT '',
            body_html TEXT NOT NULL DEFAULT '',
            mood TEXT NOT NULL DEFAULT '',
            tech_tags TEXT NOT NULL DEFAULT '[]',
            os_tags TEXT NOT NULL DEFAULT '[]',
            image_url TEXT NOT NULL DEFAULT '',
            youtube_url TEXT NOT NULL DEFAULT '',
            is_published INTEGER NOT NULL DEFAULT 0,
            published_at TEXT,
            author_user_id INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    SQL);

        pmEnsureColumn($pdo, 'blog_posts', 'os_tags', "TEXT NOT NULL DEFAULT '[]'");

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_name TEXT NOT NULL,
            sender_email TEXT NOT NULL,
            subject TEXT NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            is_archived INTEGER NOT NULL DEFAULT 0,
            ip_address TEXT NOT NULL DEFAULT '',
            user_agent TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS contact_ip_blacklist (
            ip_address TEXT PRIMARY KEY,
            reason TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS qualifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            provider TEXT NOT NULL DEFAULT '',
            qualification_type TEXT NOT NULL DEFAULT 'formal',
            description TEXT NOT NULL DEFAULT '',
            obtained_date TEXT NOT NULL DEFAULT '',
            expiry_date TEXT NOT NULL DEFAULT '',
            credential_url TEXT NOT NULL DEFAULT '',
            evidence_file TEXT NOT NULL DEFAULT '',
            show_on_qualifications_page INTEGER NOT NULL DEFAULT 1,
            available_for_cv INTEGER NOT NULL DEFAULT 1,
            display_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS cv_profile (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            full_name TEXT NOT NULL DEFAULT '',
            headline TEXT NOT NULL DEFAULT '',
            email TEXT NOT NULL DEFAULT '',
            phone TEXT NOT NULL DEFAULT '',
            location TEXT NOT NULL DEFAULT '',
            website TEXT NOT NULL DEFAULT '',
            linkedin TEXT NOT NULL DEFAULT '',
            summary TEXT NOT NULL DEFAULT '',
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS cv_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employer_name TEXT NOT NULL,
            role_title TEXT NOT NULL,
            employment_type TEXT NOT NULL DEFAULT '',
            location TEXT NOT NULL DEFAULT '',
            start_month TEXT NOT NULL DEFAULT '',
            start_year TEXT NOT NULL DEFAULT '',
            end_month TEXT NOT NULL DEFAULT '',
            end_year TEXT NOT NULL DEFAULT '',
            is_current INTEGER NOT NULL DEFAULT 0,
            summary TEXT NOT NULL DEFAULT '',
            display_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS cv_job_bullets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER NOT NULL,
            bullet_text TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'general',
            is_default INTEGER NOT NULL DEFAULT 1,
            display_order INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (job_id) REFERENCES cv_jobs(id) ON DELETE CASCADE
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS cv_skills (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            skill_name TEXT NOT NULL,
            skill_group TEXT NOT NULL DEFAULT 'General',
            description TEXT NOT NULL DEFAULT '',
            is_visible INTEGER NOT NULL DEFAULT 1,
            display_order INTEGER NOT NULL DEFAULT 0
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS cv_builds (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            build_name TEXT NOT NULL,
            target_role TEXT NOT NULL DEFAULT '',
            tailored_summary TEXT NOT NULL DEFAULT '',
            template_key TEXT NOT NULL DEFAULT 'ats_clean',
            is_public INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS cv_build_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            build_id INTEGER NOT NULL,
            item_type TEXT NOT NULL,
            item_id INTEGER NOT NULL,
            display_order INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (build_id) REFERENCES cv_builds(id) ON DELETE CASCADE
        )
    SQL);

    pmSeedDefaultAdmin($pdo);
    pmSeedDefaultSettings($pdo);
    pmSeedDefaultModules($pdo);
    pmSeedDefaultTechItems($pdo);
    $pdo->exec("DELETE FROM remember_tokens WHERE datetime(expires_at) < datetime('now')");
    $pdo->exec("DELETE FROM password_reset_tokens WHERE datetime(expires_at) < datetime('now')");
}

/**
 * Seeds the editable technology catalogue with Portfolio Manager defaults.
 *
 * The tech_items table is the editable catalogue source used by project and
 * blog selectors instead of large hardcoded arrays. Defaults are inserted
 * only when a key does not already exist, which protects future user edits to
 * labels, categories, active status, and icons.
 *
 * Package manager entries are intentionally not seeded. They are usually build
 * tooling rather than portfolio/blog technologies, and users can add any they
 * want later through Site Management once the editor is wired to this table.
 *
 * @param PDO $pdo Active database connection.
 * @return void
 */
function pmSeedDefaultTechItems(PDO $pdo): void
{
    $insert = $pdo->prepare(<<<'SQL'
        INSERT OR IGNORE INTO tech_items (
            tech_key,
            label,
            category,
            icon_path,
            is_default,
            is_active,
            display_order,
            updated_at
        ) VALUES (
            :tech_key,
            :label,
            :category,
            :icon_path,
            1,
            1,
            :display_order,
            CURRENT_TIMESTAMP
        )
    SQL);

    $displayOrder = 10;

    foreach (pmDefaultTechCatalogue() as $techKey => $item) {
        $category = (string) ($item['category'] ?? 'misc');

        if (!array_key_exists($category, pmTechCategories())) {
            $category = 'misc';
        }

        $insert->execute([
            ':tech_key' => (string) $techKey,
            ':label' => (string) ($item['label'] ?? $techKey),
            ':category' => $category,
            ':icon_path' => (string) ($item['icon'] ?? ''),
            ':display_order' => $displayOrder,
        ]);

        $displayOrder += 10;
    }

    // If an earlier development database already received package-manager
    // records, hide them so the catalogue matches the new v1.0.0 direction.
    $cleanup = $pdo->prepare(<<<'SQL'
        UPDATE tech_items
        SET is_active = 0,
            updated_at = CURRENT_TIMESTAMP
        WHERE category = 'package_managers'
           OR tech_key IN ('npm', 'bun')
    SQL);
    $cleanup->execute();

    // Existing development databases may already have the game engines from an
    // earlier seed stored under Frameworks / Libraries. Move those rows into
    // the dedicated Game Engines category without touching user-edited labels,
    // icons, visibility, or custom records.
    $gameEngineMigration = $pdo->prepare(<<<'SQL'
        UPDATE tech_items
        SET category = 'game_engines',
            updated_at = CURRENT_TIMESTAMP
        WHERE tech_key IN ('godot', 'unity', 'unrealengine', 'bevy')
    SQL);
    $gameEngineMigration->execute();
}

/**
 * Adds a missing column to an existing SQLite table.
 *
 * SQLite has limited ALTER TABLE support, so this helper only adds simple new
 * columns needed by staged migrations.
 *
 * @param PDO $pdo Active database connection.
 * @param string $table Table name to inspect.
 * @param string $column Column name to add if missing.
 * @param string $definition SQL column definition after the column name.
 * @return void
 */
function pmEnsureColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    $columns = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll();
    $columnNames = array_map(static fn (array $row): string => (string) $row['name'], $columns);

    if (!in_array($column, $columnNames, true)) {
        $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    }
}

/**
 * Seeds a default primary owner when no users exist.
 *
 * This keeps the staged review build immediately testable. The later setup
 * wizard should replace this default-account behaviour.
 *
 * @param PDO $pdo Active database connection.
 * @return void
 */
function pmSeedDefaultAdmin(PDO $pdo): void
{
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($userCount > 0) {
        pmPromoteFirstUserIfNeeded($pdo);
        return;
    }

    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO users (username, display_name, password_hash, role, is_primary_owner, is_active)
        VALUES (:username, :display_name, :password_hash, 'owner', 1, 1)
    SQL);

    $stmt->execute([
        ':username' => PM_DEFAULT_ADMIN_USERNAME,
        ':display_name' => 'Primary Owner',
        ':password_hash' => PM_DEFAULT_ADMIN_PASSWORD_HASH,
    ]);

    pmSetUserPermissions($pdo, (int) $pdo->lastInsertId(), pmPermissionKeys());
}

/**
 * Ensures older databases still have exactly one primary owner.
 *
 * @param PDO $pdo Active database connection.
 * @return void
 */
function pmPromoteFirstUserIfNeeded(PDO $pdo): void
{
    $ownerCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_primary_owner = 1')->fetchColumn();

    if ($ownerCount > 0) {
        return;
    }

    $firstUserId = (int) $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();

    if ($firstUserId > 0) {
        $stmt = $pdo->prepare('UPDATE users SET is_primary_owner = 1, role = :role WHERE id = :id');
        $stmt->execute([':role' => 'owner', ':id' => $firstUserId]);
        pmSetUserPermissions($pdo, $firstUserId, pmPermissionKeys());
    }
}

/**
 * Seeds default site title and public CV pointer settings.
 *
 * @param PDO $pdo Active database connection.
 * @return void
 */
function pmSeedDefaultSettings(PDO $pdo): void
{
    $defaults = [
        'site_title' => PM_APP_NAME,
        'public_cv_build_id' => '',
        'mailer_mode' => 'disabled',
    ];

    foreach ($defaults as $key => $value) {
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES (:key, :value)');
        $stmt->execute([':key' => $key, ':value' => $value]);
    }
}

/**
 * Seeds module visibility rows used by the dynamic router and header.
 *
 * @param PDO $pdo Active database connection.
 * @return void
 */
function pmSeedDefaultModules(PDO $pdo): void
{
    $modules = [
        ['portfolio', 'Portfolio', 1, 1, 10],
        ['blog', 'Blog', 1, 1, 20],
        ['contact', 'Contact Me', 1, 1, 30],
        ['qualifications', 'Qualifications', 1, 1, 40],
        ['cv', 'CV Download', 1, 1, 50],
    ];

    $stmt = $pdo->prepare(<<<'SQL'
        INSERT OR IGNORE INTO site_modules (module_key, module_label, is_enabled, is_public, display_order)
        VALUES (:key, :label, :enabled, :public, :order)
    SQL);

    foreach ($modules as [$key, $label, $enabled, $public, $order]) {
        $stmt->execute([
            ':key' => $key,
            ':label' => $label,
            ':enabled' => $enabled,
            ':public' => $public,
            ':order' => $order,
        ]);
    }
}

/**
 * Returns every supported permission key.
 *
 * @return array<int, string> Permission identifiers.
 */
function pmPermissionKeys(): array
{
    return [
        'can_manage_projects',
        'can_manage_blog',
        'can_manage_contact',
        'can_manage_qualifications',
        'can_manage_cv',
        'can_manage_users',
        'can_manage_site_settings',
    ];
}

/**
 * Assigns a complete permission list to a user.
 *
 * @param PDO $pdo Active database connection.
 * @param int $userId User ID to update.
 * @param array<int, string> $allowedPermissions Permission keys to enable.
 * @return void
 */
function pmSetUserPermissions(PDO $pdo, int $userId, array $allowedPermissions): void
{
    $allowedPermissions = array_values(array_intersect($allowedPermissions, pmPermissionKeys()));
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO user_permissions (user_id, permission_key, can_access)
        VALUES (:user_id, :permission_key, :can_access)
        ON CONFLICT(user_id, permission_key) DO UPDATE SET can_access = excluded.can_access
    SQL);

    foreach (pmPermissionKeys() as $permissionKey) {
        $stmt->execute([
            ':user_id' => $userId,
            ':permission_key' => $permissionKey,
            ':can_access' => in_array($permissionKey, $allowedPermissions, true) ? 1 : 0,
        ]);
    }
}

/**
 * Finds an active user by username or email address.
 *
 * @param string $login Username or email value.
 * @return array<string, mixed>|null Matching user or null.
 */
function pmFindUserByLogin(string $login): ?array
{
    $stmt = pmDb()->prepare(<<<'SQL'
        SELECT * FROM users
        WHERE is_active = 1
          AND (username = :login COLLATE NOCASE OR email = :login COLLATE NOCASE)
        LIMIT 1
    SQL);

    $stmt->execute([':login' => trim($login)]);
    $user = $stmt->fetch();

    return is_array($user) ? $user : null;
}

/**
 * Finds a user by numeric ID.
 *
 * @param int $userId User ID.
 * @return array<string, mixed>|null Matching user or null.
 */
function pmFindUserById(int $userId): ?array
{
    $stmt = pmDb()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    return is_array($user) ? $user : null;
}

/**
 * Authenticates a login attempt and creates the admin session.
 *
 * @param string $login Username or email submitted by the user.
 * @param string $password Plaintext password submitted by the user.
 * @param bool $rememberMe Whether a persistent remember-me token should be created.
 * @return bool True when authentication succeeds.
 */
function pmLogin(string $login, string $password, bool $rememberMe = false): bool
{
    $user = pmFindUserByLogin($login);

    if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
        return false;
    }

    pmStartSession();
    session_regenerate_id(true);

    $_SESSION['pm_user_id'] = (int) $user['id'];
    $_SESSION['pm_username'] = (string) $user['username'];
    $_SESSION['pm_display_name'] = pmUserDisplayName($user);

    $stmt = pmDb()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([':id' => (int) $user['id']]);

    if ($rememberMe) {
        pmCreateRememberToken((int) $user['id']);
    } else {
        pmClearRememberTokenCookie();
    }

    return true;
}

/**
 * Logs the current user out and clears session/remember-me state.
 *
 * @return void
 */
function pmLogout(): void
{
    pmStartSession();

    if (isset($_COOKIE[PM_REMEMBER_COOKIE])) {
        pmDeleteRememberToken((string) $_COOKIE[PM_REMEMBER_COOKIE]);
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], pmCookieSecure(), true);
    }

    pmClearRememberTokenCookie();
    session_destroy();
}

/**
 * Returns the current user ID from the session.
 *
 * @return int|null Current user ID or null.
 */
function pmCurrentUserId(): ?int
{
    pmStartSession();

    return isset($_SESSION['pm_user_id']) ? (int) $_SESSION['pm_user_id'] : null;
}

/**
 * Returns the current authenticated user record.
 *
 * @return array<string, mixed>|null Current user or null.
 */
function pmCurrentUser(): ?array
{
    $userId = pmCurrentUserId();

    return $userId !== null ? pmFindUserById($userId) : null;
}

/**
 * Returns a user-friendly display name for a user record.
 *
 * @param array<string, mixed> $user User record.
 * @return string Display name with username fallback.
 */
function pmUserDisplayName(array $user): string
{
    $displayName = trim((string) ($user['display_name'] ?? ''));

    return $displayName !== '' ? $displayName : (string) ($user['username'] ?? 'User');
}

/**
 * Returns the currently logged-in username.
 *
 * @return string|null Username or null.
 */
function pmLoggedInUsername(): ?string
{
    $user = pmCurrentUser();

    return $user !== null ? (string) $user['username'] : null;
}

/**
 * Returns the currently logged-in display name.
 *
 * @return string|null Display name or null.
 */
function pmLoggedInDisplayName(): ?string
{
    $user = pmCurrentUser();

    return $user !== null ? pmUserDisplayName($user) : null;
}

/**
 * Determines whether a user has an active session or valid remember-me token.
 *
 * @return bool True when logged in.
 */
function pmIsLoggedIn(): bool
{
    pmStartSession();

    if (isset($_SESSION['pm_user_id'])) {
        return pmCurrentUser() !== null;
    }

    return pmLoginFromRememberToken();
}

/**
 * Requires the current visitor to be logged in.
 *
 * @return void
 */
function pmRequireLogin(): void
{
    if (!pmIsLoggedIn()) {
        header('Location: index.php?page=adminlogonportal&error=session');
        exit;
    }
}

/**
 * Requires a specific permission for the current user.
 *
 * @param string $permissionKey Permission key to check.
 * @return void
 */
function pmRequirePermission(string $permissionKey): void
{
    pmRequireLogin();

    if (!pmHasPermission($permissionKey)) {
        http_response_code(403);
        echo 'Permission denied.';
        exit;
    }
}

/**
 * Checks whether the current user has a permission.
 *
 * Primary owners always have every permission so they cannot lock themselves out.
 *
 * @param string $permissionKey Permission key to check.
 * @return bool True when access is allowed.
 */
function pmHasPermission(string $permissionKey): bool
{
    $user = pmCurrentUser();

    if ($user === null) {
        return false;
    }

    if ((int) ($user['is_primary_owner'] ?? 0) === 1) {
        return true;
    }

    $stmt = pmDb()->prepare(<<<'SQL'
        SELECT can_access FROM user_permissions
        WHERE user_id = :user_id AND permission_key = :permission_key
        LIMIT 1
    SQL);

    $stmt->execute([
        ':user_id' => (int) $user['id'],
        ':permission_key' => $permissionKey,
    ]);

    return (int) $stmt->fetchColumn() === 1;
}

/**
 * Creates a persistent remember-me token for a user.
 *
 * Only the selector and hashed token are stored in the database. The raw token
 * is sent to the browser once inside an HTTP-only cookie.
 *
 * @param int $userId User ID.
 * @return void
 */
function pmCreateRememberToken(int $userId): void
{
    $selector = bin2hex(random_bytes(9));
    $token = bin2hex(random_bytes(32));
    $expires = (new DateTimeImmutable('+' . PM_REMEMBER_DAYS . ' days'))->format('Y-m-d H:i:s');

    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at, user_agent, ip_address)
        VALUES (:user_id, :selector, :token_hash, :expires_at, :user_agent, :ip_address)
    SQL);

    $stmt->execute([
        ':user_id' => $userId,
        ':selector' => $selector,
        ':token_hash' => hash('sha256', $token),
        ':expires_at' => $expires,
        ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
    ]);

    setcookie(PM_REMEMBER_COOKIE, $selector . ':' . $token, [
        'expires' => time() + (PM_REMEMBER_DAYS * 86400),
        'path' => '/',
        'secure' => pmCookieSecure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Attempts to authenticate using the remember-me cookie.
 *
 * @return bool True when the cookie maps to a valid unexpired token.
 */
function pmLoginFromRememberToken(): bool
{
    $cookie = isset($_COOKIE[PM_REMEMBER_COOKIE]) ? (string) $_COOKIE[PM_REMEMBER_COOKIE] : '';

    if ($cookie === '' || !str_contains($cookie, ':')) {
        return false;
    }

    [$selector, $token] = explode(':', $cookie, 2);

    $stmt = pmDb()->prepare(<<<'SQL'
        SELECT remember_tokens.*, users.username, users.display_name, users.is_active
        FROM remember_tokens
        INNER JOIN users ON users.id = remember_tokens.user_id
        WHERE remember_tokens.selector = :selector
          AND datetime(remember_tokens.expires_at) > datetime('now')
        LIMIT 1
    SQL);

    $stmt->execute([':selector' => $selector]);
    $row = $stmt->fetch();

    if (!is_array($row) || (int) $row['is_active'] !== 1 || !hash_equals((string) $row['token_hash'], hash('sha256', $token))) {
        pmClearRememberTokenCookie();
        return false;
    }

    pmStartSession();
    session_regenerate_id(true);
    $_SESSION['pm_user_id'] = (int) $row['user_id'];
    $_SESSION['pm_username'] = (string) $row['username'];
    $_SESSION['pm_display_name'] = pmUserDisplayName($row);

    $update = pmDb()->prepare('UPDATE remember_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE id = :id');
    $update->execute([':id' => (int) $row['id']]);

    return true;
}

/**
 * Deletes a remember-me token represented by the cookie value.
 *
 * @param string $cookie Cookie value in selector:token format.
 * @return void
 */
function pmDeleteRememberToken(string $cookie): void
{
    if (!str_contains($cookie, ':')) {
        return;
    }

    [$selector] = explode(':', $cookie, 2);
    $stmt = pmDb()->prepare('DELETE FROM remember_tokens WHERE selector = :selector');
    $stmt->execute([':selector' => $selector]);
}

/**
 * Clears the browser remember-me cookie.
 *
 * @return void
 */
function pmClearRememberTokenCookie(): void
{
    setcookie(PM_REMEMBER_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => pmCookieSecure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Stores or clears the non-sensitive remembered username cookie.
 *
 * @param string $username Username to remember.
 * @param bool $remember Whether to keep the username.
 * @return void
 */
function pmStoreRememberedUsername(string $username, bool $remember): void
{
    setcookie(PM_REMEMBER_USERNAME_COOKIE, $remember ? $username : '', [
        'expires' => $remember ? time() + (PM_REMEMBER_DAYS * 86400) : time() - 3600,
        'path' => '/',
        'secure' => pmCookieSecure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Returns the remembered username cookie value.
 *
 * @return string Remembered username or empty string.
 */
function pmRememberedUsername(): string
{
    return isset($_COOKIE[PM_REMEMBER_USERNAME_COOKIE]) ? (string) $_COOKIE[PM_REMEMBER_USERNAME_COOKIE] : '';
}

/**
 * Returns or creates the current CSRF token.
 *
 * @return string CSRF token.
 */
function pmCsrfToken(): string
{
    pmStartSession();

    if (empty($_SESSION['pm_csrf_token'])) {
        $_SESSION['pm_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['pm_csrf_token'];
}

/**
 * Validates a submitted CSRF token.
 *
 * @param string|null $token Submitted token.
 * @return bool True when valid.
 */
function pmValidateCsrfToken(?string $token): bool
{
    pmStartSession();

    return is_string($token)
        && isset($_SESSION['pm_csrf_token'])
        && hash_equals((string) $_SESSION['pm_csrf_token'], $token);
}
