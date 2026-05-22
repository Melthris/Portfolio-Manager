<?php
/**
 * Dynamic theme CSS endpoint.
 *
 * Site Management stores a safe allow-list of Portfolio Manager-style
 * CSS variables. This outputs only approved variables so users can customise
 * colours without arbitrary CSS injection.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

header('Content-Type: text/css; charset=utf-8');

/**
 * Returns CSS variables that may be overridden through Site Management.
 *
 * @return array<int, string> Allowed variable names.
 */
function pmAllowedThemeVariables(): array
{
    return [
        '--prime-highlight-color',
        '--prime-color1',
        '--prime-color2',
        '--prime-color3',
        '--second-highlight-color',
        '--second-color1',
        '--second-color2',
        '--third-color1',
        '--header-background-color',
        '--page-gradient-start',
        '--page-gradient-end',
        '--window-gradient-start',
        '--window-gradient-end',
        '--border-color',
        '--border-accent-soft',
        '--danger-color',
        '--radius-md',
        '--radius-lg',
    ];
}

/**
 * Returns whether a stored CSS value is safe to output.
 *
 * @param string $value Stored CSS value.
 * @return bool True when the value matches a conservative allow-list.
 */
function pmIsSafeThemeValue(string $value): bool
{
    return (bool) preg_match('/^(#[0-9a-fA-F]{3,8}|[0-9.]+rem|[0-9.]+px|[a-zA-Z0-9 (),.%_-]+)$/', $value);
}

/**
 * Returns a six-digit hex colour with an appended alpha channel.
 *
 * @param string $hexColour Six-digit colour value.
 * @param string $alpha Two-character hex alpha value.
 * @return string|null Eight-digit colour or null when the source value is invalid.
 */
function pmThemeHexWithAlpha(string $hexColour, string $alpha): ?string
{
    $normalised = trim($hexColour);

    if (preg_match('/^#[0-9a-fA-F]{6}$/', $normalised) !== 1 || preg_match('/^[0-9a-fA-F]{2}$/', $alpha) !== 1) {
        return null;
    }

    return $normalised . strtolower($alpha);
}

echo ":root {\n";
try {
    $stmt = pmDb()->query('SELECT setting_key, setting_value FROM theme_settings');
    $allowed = pmAllowedThemeVariables();
    $storedValues = [];

    foreach ($stmt->fetchAll() as $row) {
        $key = (string) $row['setting_key'];
        $value = trim((string) $row['setting_value']);

        if (in_array($key, $allowed, true) && $value !== '' && pmIsSafeThemeValue($value)) {
            $storedValues[$key] = $value;
            echo '  ' . $key . ': ' . $value . ";\n";
        }
    }

    $borderColour = $storedValues['--border-color'] ?? null;

    if (is_string($borderColour)) {
        $borderMid = pmThemeHexWithAlpha($borderColour, '26');
        $borderStrong = pmThemeHexWithAlpha($borderColour, '98');

        if ($borderMid !== null && $borderStrong !== null) {
            echo '  --border-accent-mid: ' . $borderMid . ";\n";
            echo '  --border-accent-strong: ' . $borderStrong . ";\n";
            echo '  --focus-ring: ' . $borderMid . ";\n";
        }
    }
} catch (Throwable) {
    // If the database is unavailable, the static stylesheet remains in control.
}
echo "}\n";
