<?php
/**
 * Portfolio Manager application configuration.
 *
 * This file contains generic defaults only. Site-specific branding, colours,
 * and enabled modules can be changed later through Site Management.
 */

declare(strict_types=1);

require_once __DIR__ . '/version.php';

const PM_APP_NAME = 'Portfolio Manager';
const PM_DEFAULT_PAGE = 'home';
const PM_SESSION_NAME = 'PortfolioManagerSession';
const PM_REMEMBER_COOKIE = 'PortfolioManagerRememberMe';
const PM_REMEMBER_USERNAME_COOKIE = 'PortfolioManagerRememberUsername';
const PM_REMEMBER_DAYS = 30;
const PM_DEFAULT_ADMIN_USERNAME = 'Admin';

// Default password for review builds only: ChangeMeNow!2026
// Change this after first login or replace seeding with an installer before release.
const PM_DEFAULT_ADMIN_PASSWORD_HASH = '$2y$12$W/S66/NQ90CJowOKJKutaOsNI7Pkyi3eX9BH8Q/QG5rE7pUBt4eWW';

/**
 * Escapes a value for safe HTML output.
 *
 * @param mixed $value Value to escape.
 * @return string HTML-safe value.
 */
function pmEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Returns the fallback application name.
 *
 * @return string Application name.
 */
function pmAppNameFallback(): string
{
    return PM_APP_NAME;
}

/**
 * Returns a browser title using the Portfolio Manager app name.
 *
 * @param string $pageLabel Human-readable page title.
 * @return string Browser title text.
 */
function pmPageTitle(string $pageLabel): string
{
    return trim($pageLabel) . ' | ' . pmAppNameFallback();
}
