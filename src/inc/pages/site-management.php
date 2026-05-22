<?php
/**
 * Site Management page.
 *
 * This page controls public modules, header visibility, the site title, home
 * page content, editable colour variables, and custom technology catalogue
 * items. The site settings controls use the HTML `form` attribute so the
 * custom technology upload/delete forms can sit visually inside the same admin
 * workspace without creating invalid nested forms.
 */

declare(strict_types=1);

pmRequirePermission('can_manage_site_settings');
$modules = pmDb()->query('SELECT * FROM site_modules ORDER BY display_order ASC')->fetchAll();
$themeRows = pmDb()->query('SELECT setting_key, setting_value FROM theme_settings')->fetchAll();
$theme = [];

foreach ($themeRows as $row) {
    $theme[(string) $row['setting_key']] = (string) $row['setting_value'];
}

$themeDefaults = pmDefaultThemeVariables();

$themeLabels = [
    '--prime-highlight-color' => 'Primary highlight',
    '--prime-color1' => 'Primary text',
    '--prime-color2' => 'Secondary text',
    '--prime-color3' => 'Muted accent',
    '--second-highlight-color' => 'Secondary highlight',
    '--second-color1' => 'Main accent',
    '--second-color2' => 'Secondary accent',
    '--third-color1' => 'Base background',
    '--header-background-color' => 'Header background',
    '--page-gradient-start' => 'Page gradient top',
    '--page-gradient-end' => 'Page gradient bottom',
    '--window-gradient-start' => 'Window gradient start',
    '--window-gradient-end' => 'Window gradient end',
    '--border-color' => 'Border colour',
    '--border-accent-soft' => 'Soft border',
    '--danger-color' => 'Danger colour',
];

$homeDefaults = pmDefaultHomeContent();
$homeSubheading = pmHomeSubheading();
$homeBodyText = pmHomeBodyText();
$contactDefaults = pmDefaultContactContent();
$contactHeading = pmContactHeading();
$contactBody = pmContactBody();
$contactCards = pmContactCards(true);
$technologyRows = pmTechnologyRowsForManagement();
$techCategories = pmTechCategories();
$socialLinks = pmAllSocialLinks();

$statusMessages = [
    'saved' => ['success', 'Site settings saved.'],
    'restored' => ['success', 'Site settings restored to the Portfolio Manager defaults.'],
    'invalid' => ['error', 'Your session expired. Please try again.'],
    'tech-added' => ['success', 'Technology catalogue item added.'],
    'tech-deleted' => ['success', 'Technology item removed or hidden.'],
    'tech-updated' => ['success', 'Technology catalogue item updated.'],
    'tech-invalid' => ['error', 'Custom technology item could not be saved. Check the label, category, and icon file.'],
    'social-saved' => ['success', 'Social media links saved.'],
    'social-deleted' => ['success', 'Social media link hidden or deleted.'],
    'social-invalid' => ['error', 'Social media link could not be saved. Check the label, URL, and icon file.'],
];
$status = pmString($_GET['status'] ?? '');
$flash = $statusMessages[$status] ?? null;

/**
 * Returns a safe hex colour for colour picker inputs.
 *
 * @param string|null $value Stored colour value.
 * @param string $fallback Fallback colour value.
 * @return string Browser-safe six-digit hex colour.
 */
function pmSiteManagementColourValue(?string $value, string $fallback): string
{
    $candidate = trim((string) $value);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $candidate) === 1 ? $candidate : $fallback;
}
?>

<section class="manage-projects-page site-management-page">
    <div class="manage-projects-header">
        <div>
            <p class="admin-kicker">Admin Portal</p>
            <h1 class="admin-page-title">Site Management</h1>
            <p class="admin-page-subtitle">
                Control page modules, dynamic header visibility, page content, social links, and Portfolio Manager colour variables.
            </p>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <p class="contact-flash contact-flash-<?= pmEscape($flash[0]) ?>" role="alert"><?= pmEscape($flash[1]) ?></p>
    <?php endif; ?>

    <form id="site-settings-form" method="post" action="src/inc/site-settings-handler.php" class="site-settings-hidden-form">
        <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
        <input type="hidden" name="settings_action" value="save" data-site-settings-action>
    </form>

    <div class="manage-projects-layout site-management-layout">
        <aside class="project-admin-sidebar site-module-sidebar">
            <div class="admin-panel-heading">
                <h2>Public Modules</h2>
                <p>Choose which sections exist publicly and whether they appear in the dynamic header.</p>
            </div>

            <div class="site-module-list">
                <?php foreach ($modules as $module): ?>
                    <article class="site-module-card">
                        <div>
                            <strong><?= pmEscape((string) $module['module_label']) ?></strong>
                            <small><?= pmEscape((string) $module['module_key']) ?></small>
                        </div>

                        <label class="module-toggle-row">
                            <input
                                form="site-settings-form"
                                type="checkbox"
                                name="module_enabled[<?= pmEscape((string) $module['module_key']) ?>]"
                                <?= (int) $module['is_enabled'] === 1 ? 'checked' : '' ?>
                                data-module-default="checked"
                            >
                            <span>Enabled</span>
                        </label>

                        <label class="module-toggle-row">
                            <input
                                form="site-settings-form"
                                type="checkbox"
                                name="module_public[<?= pmEscape((string) $module['module_key']) ?>]"
                                <?= (int) $module['is_public'] === 1 ? 'checked' : '' ?>
                                data-module-default="checked"
                            >
                            <span>Public / in header</span>
                        </label>
                    </article>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="project-admin-workspace site-management-workspace">
            <article class="admin-card site-content-settings-card">
                <div class="admin-card-header">
                    <div>
                        <p class="admin-kicker">Content</p>
                        <h2>Page Content</h2>
                        <p>Choose a page section below, then update the public-facing text for that section.</p>
                    </div>
                </div>

                <div class="site-content-tabs" role="tablist" aria-label="Page content sections">
                    <button type="button" class="site-content-tab is-active" data-site-content-tab="home" role="tab" aria-selected="true" aria-controls="site-content-panel-home">
                        Home Page
                    </button>
                    <button type="button" class="site-content-tab" data-site-content-tab="contact" role="tab" aria-selected="false" aria-controls="site-content-panel-contact">
                        Contact Me
                    </button>
                    <button type="button" class="site-content-tab" data-site-content-tab="socials" role="tab" aria-selected="false" aria-controls="site-content-panel-socials">
                        Social Media
                    </button>
                </div>

                <div class="site-content-panels">
                    <section id="site-content-panel-home" class="site-content-panel is-active" data-site-content-panel="home" role="tabpanel">
                        <div class="admin-card-header compact-card-header">
                            <div>
                                <p class="admin-kicker">Identity</p>
                                <h3>Site Title &amp; Home Page Content</h3>
                            </div>
                        </div>

                        <div class="admin-form-grid">
                            <label class="admin-field admin-field-wide">
                                <span>Site title</span>
                                <input form="site-settings-form" class="textbox" name="site_title" value="<?= pmEscape(pmAppName()) ?>" data-site-title-input>
                            </label>

                            <label class="admin-field admin-field-wide">
                                <span>Sub-heading under site title</span>
                                <input
                                    form="site-settings-form"
                                    class="textbox"
                                    name="home_subheading"
                                    value="<?= pmEscape($homeSubheading) ?>"
                                    data-home-subheading-input
                                    data-home-subheading-default="<?= pmEscape($homeDefaults['subheading']) ?>"
                                >
                            </label>

                            <label class="admin-field admin-field-wide">
                                <span>Text below the sub-heading</span>
                                <textarea
                                    form="site-settings-form"
                                    class="textbox home-copy-textarea"
                                    name="home_body_text"
                                    rows="5"
                                    data-home-body-input
                                    data-home-body-default="<?= pmEscape($homeDefaults['body']) ?>"
                                ><?= pmEscape($homeBodyText) ?></textarea>
                            </label>
                        </div>
                    </section>

                    <section id="site-content-panel-contact" class="site-content-panel" data-site-content-panel="contact" role="tabpanel" hidden>
                        <div class="admin-card-header compact-card-header">
                            <div>
                                <p class="admin-kicker">Contact</p>
                                <h3>Contact Me Content</h3>
                                <p>Update the public Contact Me information panel beside the message form.</p>
                            </div>
                        </div>

                        <div class="admin-form-grid">
                            <label class="admin-field admin-field-wide">
                                <span>Information panel heading</span>
                                <input
                                    form="site-settings-form"
                                    class="textbox"
                                    name="contact_heading"
                                    value="<?= pmEscape($contactHeading) ?>"
                                    data-contact-heading-default="<?= pmEscape($contactDefaults['heading']) ?>"
                                >
                            </label>

                            <label class="admin-field admin-field-wide">
                                <span>Information panel text</span>
                                <textarea
                                    form="site-settings-form"
                                    class="textbox home-copy-textarea"
                                    name="contact_body"
                                    rows="5"
                                    data-contact-body-default="<?= pmEscape($contactDefaults['body']) ?>"
                                ><?= pmEscape($contactBody) ?></textarea>
                            </label>
                        </div>

                        <div class="contact-card-settings">
                            <h4>Information boxes</h4>
                            <p class="empty-state-small">Use up to three short boxes. Untick a box to remove it from the public Contact Me panel.</p>

                            <?php for ($cardIndex = 1; $cardIndex <= 3; $cardIndex++): ?>
                                <?php
                                $card = $contactCards[$cardIndex] ?? [
                                    'index' => $cardIndex,
                                    'label' => $contactDefaults['cards'][$cardIndex]['label'] ?? '',
                                    'text' => $contactDefaults['cards'][$cardIndex]['text'] ?? '',
                                    'enabled' => (int) ($contactDefaults['cards'][$cardIndex]['enabled'] ?? 0),
                                ];
                                ?>
                                <fieldset class="contact-card-setting">
                                    <legend>Box <?= $cardIndex ?></legend>

                                    <label class="module-toggle-row">
                                        <input
                                            form="site-settings-form"
                                            type="checkbox"
                                            name="contact_cards[<?= $cardIndex ?>][enabled]"
                                            <?= ((int) $card['enabled'] === 1) ? 'checked' : '' ?>
                                            data-contact-card-enabled-default="<?= (int) ($contactDefaults['cards'][$cardIndex]['enabled'] ?? 0) === 1 ? 'checked' : '' ?>"
                                        >
                                        <span>Show this box</span>
                                    </label>

                                    <label class="admin-field">
                                        <span>Small heading</span>
                                        <input
                                            form="site-settings-form"
                                            class="textbox"
                                            name="contact_cards[<?= $cardIndex ?>][label]"
                                            value="<?= pmEscape($card['label']) ?>"
                                            data-contact-card-label-default="<?= pmEscape($contactDefaults['cards'][$cardIndex]['label'] ?? '') ?>"
                                        >
                                    </label>

                                    <label class="admin-field">
                                        <span>Text</span>
                                        <input
                                            form="site-settings-form"
                                            class="textbox"
                                            name="contact_cards[<?= $cardIndex ?>][text]"
                                            value="<?= pmEscape($card['text']) ?>"
                                            data-contact-card-text-default="<?= pmEscape($contactDefaults['cards'][$cardIndex]['text'] ?? '') ?>"
                                        >
                                    </label>
                                </fieldset>
                            <?php endfor; ?>
                        </div>
                    </section>

                    <section id="site-content-panel-socials" class="site-content-panel" data-site-content-panel="socials" role="tabpanel" hidden>
                        <div class="admin-card-header compact-card-header">
                            <div>
                                <p class="admin-kicker">Socials</p>
                                <h3>Social Media Links</h3>
                                <p>Add social profiles, choose where they appear, and drag the preview pins into the order you want.</p>
                            </div>
                        </div>

                        <?php if ($socialLinks !== []): ?>
                            <form method="post" action="src/inc/social-links-handler.php" class="social-pin-order-form" data-social-order-form>
                                <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                <input type="hidden" name="social_action" value="save_social_order">

                                <div class="social-pin-preview-header">
                                    <div>
                                        <p class="admin-kicker">Preview</p>
                                        <h4>Social media pin preview</h4>
                                        <p>Drag the pins into the order they should appear in the footer and Contact Me page, then save the configuration.</p>
                                    </div>
                                    <button type="submit">Save pin order</button>
                                </div>

                                <div class="social-pin-preview" data-social-order-list aria-label="Social media pin display order">
                                    <?php foreach ($socialLinks as $social): ?>
                                        <?php
                                        $socialId = (int) $social['id'];
                                        $socialIcon = pmSocialIconPath((string) ($social['icon_path'] ?? ''));
                                        $socialFilter = (string) ($social['icon_filter'] ?? 'white') === 'black' ? 'black' : 'white';
                                        ?>
                                        <button
                                            type="button"
                                            class="social-pin-preview-item social-icon-filter-<?= pmEscape($socialFilter) ?><?= (int) $social['is_active'] === 1 ? '' : ' is-muted' ?>"
                                            draggable="true"
                                            data-social-order-item
                                            data-social-order-id="<?= $socialId ?>"
                                            data-social-preview-pin="<?= $socialId ?>"
                                            title="<?= pmEscape((string) $social['platform_label']) ?>"
                                        >
                                            <img src="<?= pmEscape($socialIcon) ?>" alt="" data-social-preview-icon>
                                            <span data-social-preview-label><?= pmEscape((string) $social['platform_label']) ?></span>
                                            <input type="hidden" name="social_order[]" value="<?= $socialId ?>">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="social-links-layout">
                            <form method="post" action="src/inc/social-links-handler.php" enctype="multipart/form-data" class="social-link-form social-link-add-form" data-social-add-form>
                                <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                <input type="hidden" name="social_action" value="add_social_link">
                                <input type="hidden" name="display_order" value="500">

                                <div class="admin-card-header compact-card-header">
                                    <div>
                                        <p class="admin-kicker">Add</p>
                                        <h4>Add social profile</h4>
                                    </div>
                                </div>

                                <label class="admin-field">
                                    <span>Platform label</span>
                                    <input class="textbox" name="platform_label" placeholder="Example: LinkedIn" required data-social-add-label>
                                </label>

                                <label class="admin-field">
                                    <span>Profile URL</span>
                                    <input class="textbox" name="profile_url" placeholder="https://example.com/profile">
                                </label>

                                <label class="admin-field">
                                    <span>Icon upload</span>
                                    <div class="social-upload-preview-row">
                                        <img class="social-admin-icon social-icon-filter-white" src="src/icons/unknown.svg" alt="" data-social-add-icon-preview>
                                        <input class="textbox" type="file" name="social_icon" accept=".svg,.png,image/svg+xml,image/png" data-social-add-icon-input>
                                    </div>
                                    <small>Optional. SVG or PNG, up to 1 MB. Icons are stored in src/icons/socials with the bundled social icon set.</small>
                                </label>

                                <fieldset class="social-filter-switch" data-social-filter-switch>
                                    <legend>Icon filter</legend>
                                    <label>
                                        <input type="radio" name="icon_filter" value="white" checked data-social-add-filter-toggle>
                                        <span>White</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="icon_filter" value="black" data-social-add-filter-toggle>
                                        <span>Black</span>
                                    </label>
                                </fieldset>

                                <div class="social-toggle-grid">
                                    <label class="module-toggle-row">
                                        <input type="checkbox" name="show_in_footer" value="1">
                                        <span>Show in footer</span>
                                    </label>

                                    <label class="module-toggle-row">
                                        <input type="checkbox" name="show_on_contact_page" value="1">
                                        <span>Show on Contact Me</span>
                                    </label>

                                    <label class="module-toggle-row">
                                        <input type="checkbox" name="is_active" value="1" checked>
                                        <span>Visible</span>
                                    </label>
                                </div>

                                <div class="admin-actions">
                                    <button type="submit">Add social link</button>
                                </div>
                            </form>

                            <div class="social-links-existing">
                                <div class="admin-card-header compact-card-header">
                                    <div>
                                        <p class="admin-kicker">Profiles</p>
                                        <h4>Existing social links</h4>
                                        <p>Update labels, URLs, icon filters, and visibility for each social profile.</p>
                                    </div>
                                </div>

                                <?php if ($socialLinks === []): ?>
                                    <p class="empty-state-small">No social platforms are available yet.</p>
                                <?php else: ?>
                                    <div class="social-links-grid">
                                        <?php foreach ($socialLinks as $social): ?>
                                            <?php
                                            $socialId = (int) $social['id'];
                                            $socialIcon = pmSocialIconPath((string) ($social['icon_path'] ?? ''));
                                            $isDefaultSocial = (int) ($social['is_default'] ?? 0) === 1;
                                            $socialFilter = (string) ($social['icon_filter'] ?? 'white') === 'black' ? 'black' : 'white';
                                            ?>
                                            <article class="social-link-row">
                                                <form id="social-update-<?= $socialId ?>" method="post" action="src/inc/social-links-handler.php" enctype="multipart/form-data" class="social-link-edit-form" data-social-edit-form data-social-edit-id="<?= $socialId ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                                    <input type="hidden" name="social_action" value="update_social_link">
                                                    <input type="hidden" name="social_id" value="<?= $socialId ?>">
                                                    <input type="hidden" name="display_order" value="<?= (int) $social['display_order'] ?>">

                                                    <div class="social-link-summary">
                                                        <img class="social-admin-icon social-icon-filter-<?= pmEscape($socialFilter) ?>" src="<?= pmEscape($socialIcon) ?>" alt="" loading="lazy" data-social-card-icon>
                                                        <div>
                                                            <strong data-social-card-label><?= pmEscape((string) $social['platform_label']) ?></strong>
                                                            <small><?= $isDefaultSocial ? 'Default platform' : 'Custom platform' ?></small>
                                                        </div>
                                                    </div>

                                                    <div class="social-link-fields">
                                                        <label class="admin-field">
                                                            <span>Label</span>
                                                            <input class="textbox" name="platform_label" value="<?= pmEscape((string) $social['platform_label']) ?>" required data-social-label-input>
                                                        </label>

                                                        <label class="admin-field">
                                                            <span>Profile URL</span>
                                                            <input class="textbox" name="profile_url" value="<?= pmEscape((string) $social['profile_url']) ?>" placeholder="https://example.com/profile">
                                                        </label>

                                                        <label class="admin-field">
                                                            <span>Replace icon</span>
                                                            <input class="textbox" type="file" name="social_icon" accept=".svg,.png,image/svg+xml,image/png" data-social-icon-input>
                                                        </label>
                                                    </div>

                                                    <fieldset class="social-filter-switch" data-social-filter-switch>
                                                        <legend>Icon filter</legend>
                                                        <label>
                                                            <input type="radio" name="icon_filter" value="white" <?= $socialFilter === 'white' ? 'checked' : '' ?> data-social-filter-toggle>
                                                            <span>White</span>
                                                        </label>
                                                        <label>
                                                            <input type="radio" name="icon_filter" value="black" <?= $socialFilter === 'black' ? 'checked' : '' ?> data-social-filter-toggle>
                                                            <span>Black</span>
                                                        </label>
                                                    </fieldset>

                                                    <div class="social-toggle-grid">
                                                        <label class="module-toggle-row">
                                                            <input type="checkbox" name="show_in_footer" value="1" <?= (int) $social['show_in_footer'] === 1 ? 'checked' : '' ?>>
                                                            <span>Footer</span>
                                                        </label>

                                                        <label class="module-toggle-row">
                                                            <input type="checkbox" name="show_on_contact_page" value="1" <?= (int) $social['show_on_contact_page'] === 1 ? 'checked' : '' ?>>
                                                            <span>Contact Me</span>
                                                        </label>

                                                        <label class="module-toggle-row">
                                                            <input type="checkbox" name="is_active" value="1" <?= (int) $social['is_active'] === 1 ? 'checked' : '' ?> data-social-visible-toggle>
                                                            <span>Visible</span>
                                                        </label>
                                                    </div>
                                                </form>

                                                <form id="social-delete-<?= $socialId ?>" method="post" action="src/inc/social-links-handler.php" onsubmit="return confirm('Hide or delete this social profile?');">
                                                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                                    <input type="hidden" name="social_action" value="delete_social_link">
                                                    <input type="hidden" name="social_id" value="<?= $socialId ?>">
                                                </form>

                                                <div class="admin-actions social-link-actions">
                                                    <button form="social-update-<?= $socialId ?>" type="submit">Save</button>
                                                    <button form="social-delete-<?= $socialId ?>" type="submit" class="danger-button"><?= $isDefaultSocial ? 'Hide' : 'Delete' ?></button>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                </div>
            </article>

            <article class="admin-card theme-editor-card">
                <div class="admin-card-header">
                    <div>
                        <p class="admin-kicker">Theme</p>
                        <h2>Colour Variables</h2>
                    </div>
                </div>

                <div class="theme-colour-grid">
                    <?php foreach ($themeDefaults as $key => $fallback): ?>
                        <?php $colour = pmSiteManagementColourValue($theme[$key] ?? null, $fallback); ?>
                        <label class="theme-colour-control">
                            <span class="theme-colour-label"><?= pmEscape($themeLabels[$key] ?? $key) ?></span>
                            <small><?= pmEscape($key) ?></small>
                            <input
                                form="site-settings-form"
                                type="color"
                                name="theme[<?= pmEscape($key) ?>]"
                                value="<?= pmEscape($colour) ?>"
                                data-theme-variable="<?= pmEscape($key) ?>"
                                data-theme-default="<?= pmEscape($fallback) ?>"
                                aria-label="<?= pmEscape($themeLabels[$key] ?? $key) ?> colour"
                            >
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="admin-actions site-management-actions">
                    <button form="site-settings-form" type="submit" data-site-save-button>Save site settings</button>
                    <button form="site-settings-form" type="submit" class="secondary-button" data-site-restore-button>Restore to default</button>
                </div>
            </article>

                <section class="custom-tech-management admin-card tech-catalogue-management">
                    <div class="admin-card-header">
                        <div>
                            <p class="admin-kicker">Catalogue</p>
                            <h2>Technology Catalogue</h2>
                            <p>Manage the default and custom technologies used by Portfolio, Blog, Manage Projects, and Manage Blog.</p>
                        </div>
                    </div>

                    <div class="custom-tech-layout tech-catalogue-layout">
                        <form method="post" action="src/inc/tech-catalogue-handler.php" enctype="multipart/form-data" class="custom-tech-form tech-catalogue-add-card">
                            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                            <input type="hidden" name="tech_action" value="add_tech_item">

                            <div class="tech-catalogue-panel-heading">
                                <h3>Add new item</h3>
                                <p class="empty-state-small">Add an extra stack item to Portfolio, Blog, Manage Projects, and Manage Blog selectors.</p>
                            </div>

                            <label class="admin-field">
                                <span>Technology label</span>
                                <input class="textbox" name="custom_tech_label" placeholder="Example: Prisma" required>
                            </label>

                            <label class="admin-field">
                                <span>Category</span>
                                <select class="portfolio-filter-select" name="custom_tech_category" required>
                                    <?php foreach ($techCategories as $categoryKey => $categoryLabel): ?>
                                        <option value="<?= pmEscape($categoryKey) ?>"><?= pmEscape($categoryLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label class="admin-field tech-catalogue-add-icon-field">
                                <span>Icon upload</span>
                                <input class="textbox tech-file-input" type="file" name="custom_tech_icon" accept=".svg,.png,image/svg+xml,image/png" data-tech-icon-preview-input>
                                <small>Optional. SVG or PNG, up to 1 MB. Empty uploads use the fallback unknown icon.</small>
                            </label>

                            <div class="tech-catalogue-upload-preview" data-tech-icon-preview hidden>
                                <span>Icon preview</span>
                                <img src="" alt="Selected technology icon preview">
                            </div>

                            <div class="admin-actions tech-catalogue-add-actions">
                                <button type="submit">Add technology item</button>
                            </div>
                        </form>

                        <div class="custom-tech-existing tech-catalogue-existing">
                            <div class="tech-catalogue-existing-header">
                                <div>
                                    <h3>Existing catalogue items</h3>
                                    <p class="empty-state-small">Choose a category, then edit labels, icons, categories, and selector visibility.</p>
                                </div>

                                <label class="admin-field tech-catalogue-view-field">
                                    <span>View category</span>
                                    <select class="portfolio-filter-select" data-tech-category-filter>
                                        <?php foreach ($techCategories as $categoryKey => $categoryLabel): ?>
                                            <?php $categoryRows = array_values(array_filter($technologyRows, static fn (array $item): bool => (string) ($item['category'] ?? 'misc') === $categoryKey)); ?>
                                            <?php if ($categoryRows === []): ?><?php continue; ?><?php endif; ?>
                                            <option value="<?= pmEscape($categoryKey) ?>"><?= pmEscape($categoryLabel) ?> (<?= count($categoryRows) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>

                            <?php if ($technologyRows === []): ?>
                                <p class="empty-state-small">No technology catalogue rows were found.</p>
                            <?php else: ?>
                                <div class="tech-catalogue-list" data-tech-catalogue-list>
                                    <?php $firstVisibleCategory = true; ?>
                                    <?php foreach ($techCategories as $categoryKey => $categoryLabel): ?>
                                        <?php $categoryRows = array_values(array_filter($technologyRows, static fn (array $item): bool => (string) ($item['category'] ?? 'misc') === $categoryKey)); ?>
                                        <?php if ($categoryRows === []): ?><?php continue; ?><?php endif; ?>

                                        <section class="tech-catalogue-category <?= $firstVisibleCategory ? 'is-active-category' : '' ?>" data-tech-category-section="<?= pmEscape($categoryKey) ?>">
                                            <div class="tech-catalogue-category-heading">
                                                <h4><?= pmEscape($categoryLabel) ?></h4>
                                                <span><?= count($categoryRows) ?> items</span>
                                            </div>

                                            <div class="tech-catalogue-category-items">
                                                <?php foreach ($categoryRows as $item): ?>
                                                    <?php
                                                    $itemId = (int) $item['id'];
                                                    $isDefault = ((int) ($item['is_default'] ?? 0)) === 1;
                                                    $isActive = ((int) ($item['is_active'] ?? 0)) === 1;
                                                    $iconPath = pmTechIconPath((string) ($item['icon_path'] ?? ''));
                                                    ?>
                                                    <article class="custom-tech-row tech-catalogue-row <?= $isActive ? '' : 'is-disabled-tech' ?>">
                                                        <form id="tech-update-<?= $itemId ?>" method="post" action="src/inc/tech-catalogue-handler.php" enctype="multipart/form-data" class="tech-catalogue-edit-form">
                                                            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                                            <input type="hidden" name="tech_action" value="update_tech_item">
                                                            <input type="hidden" name="tech_id" value="<?= $itemId ?>">

                                                            <div class="custom-tech-summary tech-catalogue-summary">
                                                                <img src="<?= pmEscape($iconPath) ?>" alt="" loading="lazy" data-tech-icon-preview-image>
                                                                <div class="tech-catalogue-summary-copy">
                                                                    <strong><?= pmEscape((string) $item['label']) ?></strong>
                                                                    <small><?= $isDefault ? 'Default item' : 'Custom item' ?></small>
                                                                    <label class="tech-icon-upload-control">
                                                                        <span>Replace icon</span>
                                                                        <span class="tech-icon-upload-button">Choose icon</span>
                                                                        <input type="file" name="tech_icon" accept=".svg,.png,image/svg+xml,image/png" data-tech-icon-preview-input>
                                                                    </label>

                                                                    <!-- Keep selector visibility with the icon controls so each catalogue row reads left-to-right: item details, editable fields, row actions. -->
                                                                    <label class="module-toggle-row tech-catalogue-visible-toggle">
                                                                        <input type="checkbox" name="tech_is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                                                                        <span>Visible</span>
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div class="tech-catalogue-fields">
                                                                <label class="admin-field tech-catalogue-label-field">
                                                                    <span>Label</span>
                                                                    <input class="textbox" name="tech_label" value="<?= pmEscape((string) $item['label']) ?>" required>
                                                                </label>

                                                                <label class="admin-field tech-catalogue-category-field">
                                                                    <span>Category</span>
                                                                    <select class="portfolio-filter-select" name="tech_category" required>
                                                                        <?php foreach ($techCategories as $optionKey => $optionLabel): ?>
                                                                            <option value="<?= pmEscape($optionKey) ?>" <?= $categoryKey === $optionKey ? 'selected' : '' ?>><?= pmEscape($optionLabel) ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </label>
                                                            </div>

                                                        </form>

                                                        <form id="tech-delete-<?= $itemId ?>" method="post" action="src/inc/tech-catalogue-handler.php" class="tech-catalogue-delete-form" onsubmit="return confirm('Remove this technology item from selectors? Existing project/blog labels will remain as text.');">
                                                            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                                            <input type="hidden" name="tech_action" value="delete_tech_item">
                                                            <input type="hidden" name="tech_id" value="<?= $itemId ?>">
                                                        </form>

                                                        <div class="admin-actions tech-catalogue-actions">
                                                            <button form="tech-update-<?= $itemId ?>" type="submit">Save</button>
                                                            <button form="tech-delete-<?= $itemId ?>" type="submit" class="danger-button"><?= $isDefault ? 'Hide' : 'Delete' ?></button>
                                                        </div>
                                                    </article>
                                                <?php endforeach; ?>
                                            </div>
                                        </section>

                                        <?php $firstVisibleCategory = false; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

        </section>
    </div>
</section>

<script src="src/js/site-management.js?v=<?= rawurlencode(PM_VERSION) ?>"></script>
