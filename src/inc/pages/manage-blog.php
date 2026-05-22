<?php
/**
 * Blog Management page.
 *
 * This page uses the same admin card shell as Manage Projects. It keeps the
 * Blog form focused on the fields that matter: title, mood, publish state,
 * blog post content, technology discussed, platforms, and optional media URLs.
 */

declare(strict_types=1);

pmRequirePermission('can_manage_blog');

$posts = pmGetBlogPosts(true);
$editId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$editingPost = null;

if ($editId > 0) {
    $stmt = pmDb()->prepare('SELECT * FROM blog_posts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $editingPost = $stmt->fetch() ?: null;
}

$statusMessages = [
    'saved' => ['success', 'Blog post saved.'],
    'deleted' => ['success', 'Blog post deleted.'],
    'invalid' => ['error', 'Your session expired. Please try again.'],
    'missing-title' => ['error', 'A blog title is required.'],
];
$status = pmString($_GET['status'] ?? '');
$flash = $statusMessages[$status] ?? null;

/**
 * Returns a safe form value from a blog post row.
 *
 * @param array<string, mixed>|null $post Blog post row.
 * @param string $key Field key.
 * @return string HTML-safe field value.
 */
function pmBlogFormValue(?array $post, string $key): string
{
    return pmEscape((string) ($post[$key] ?? ''));
}

/**
 * Returns the checked attribute for a published checkbox.
 *
 * @param array<string, mixed>|null $post Blog post row.
 * @return string Checked attribute or an empty string.
 */
function pmBlogPublishedChecked(?array $post): string
{
    if ($post === null) {
        return 'checked';
    }

    return ((int) ($post['is_published'] ?? 0)) === 1 ? 'checked' : '';
}

/**
 * Decodes a JSON list field from a blog post row.
 *
 * @param string $raw Raw JSON list string.
 * @return array<int, string> Selected labels.
 */
function pmBlogSelectedList(string $raw): array
{
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
}

/**
 * Formats an admin date for compact display in the post list.
 *
 * @param string $date Raw database date.
 * @return string Display date.
 */
function pmAdminFormatDate(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('M j, Y', $timestamp);
}

/**
 * Returns mood options used by the blog editor.
 *
 * The label intentionally includes a small emoji so the native dropdown matches
 * the original dropdown control behaviour without replacing it with a
 * custom button grid.
 *
 * @return array<string, string> Mood key to dropdown label map.
 */
function pmBlogMoodOptions(): array
{
    return [
        'focused' => '🧠 Focused',
        'building' => '🛠️ Building',
        'debugging' => '🐛 Debugging',
        'excited' => '🚀 Excited',
        'frustrated' => '😤 Frustrated',
        'reflective' => '🤔 Reflective',
        'learning' => '📚 Learning',
        'idea' => '💡 Idea',
        'shipped' => '🎉 Shipped',
        'coffee' => '☕ Coffee Mode',
    ];
}

/**
 * Renders the original dropdown-style mood selector used by the Blog Manager.
 *
 * This deliberately stays as a native select element so the opened menu looks
 * like the dropdown shown in the reference screenshot.
 *
 * @param string $currentMood Selected mood key.
 * @return void
 */
function pmRenderMoodSelector(string $currentMood): void
{
    ?>
    <select class="portfolio-filter-select blog-mood-select" name="mood" aria-label="Blog mood">
        <?php foreach (pmBlogMoodOptions() as $moodKey => $moodLabel): ?>
            <option value="<?= pmEscape($moodKey) ?>" <?= $currentMood === $moodKey ? 'selected' : '' ?>>
                <?= pmEscape($moodLabel) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Renders operating system/platform choices for blog posts.
 *
 * @param array<int, string> $selectedOsTags Selected platform labels.
 * @return void
 */
function pmRenderBlogOsSelector(array $selectedOsTags): void
{
    ?>
    <div class="blog-os-selector os-icon-selector">
        <?php foreach (pmOsPlatformCatalogue() as $platform): ?>
            <?php
            $label = (string) $platform['label'];
            $icon = (string) $platform['icon'];
            $checked = in_array($label, $selectedOsTags, true) ? 'checked' : '';
            ?>
            <label class="os-icon-option">
                <input type="checkbox" name="os_tags[]" value="<?= pmEscape($label) ?>" <?= $checked ?>>
                <span>
                    <img src="<?= pmEscape($icon) ?>" alt="" loading="lazy">
                    <span><?= pmEscape($label) ?></span>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Renders the Add/Edit blog form.
 *
 * @param string $mode Either "add" or "update".
 * @param array<string, mixed>|null $post Existing post when editing.
 * @return void
 */
function pmRenderBlogAdminForm(string $mode, ?array $post = null): void
{
    $isEdit = $mode === 'update';
    $actionLabel = $isEdit ? 'Update Post' : 'Add Post';
    $heading = $isEdit ? 'Edit Blog Post' : 'Add Blog Post';
    $kicker = $isEdit ? 'Existing Post' : 'New Post';
    $postId = (int) ($post['id'] ?? 0);
    $currentMood = (string) ($post['mood'] ?? 'focused');

    // Older staged builds used "released" before the icon set settled on "shipped".
    if ($currentMood === 'released') {
        $currentMood = 'shipped';
    }

    $selectedTechTags = pmBlogSelectedList((string) ($post['tech_tags'] ?? '[]'));
    $selectedOsTags = pmBlogSelectedList((string) ($post['os_tags'] ?? '[]'));
    $editorId = $isEdit ? 'editBlogContent' . $postId : 'newBlogContent';
    $categories = pmTechCategories();
    $catalogue = pmTechCatalogue();
    ?>
    <article class="admin-card blog-editor-card">
        <div class="admin-card-header">
            <div>
                <p class="admin-kicker"><?= pmEscape($kicker) ?></p>
                <h2><?= pmEscape($heading) ?></h2>
            </div>
        </div>

        <form action="src/inc/blog-handler.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
            <input type="hidden" name="action" value="save">

            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $postId ?>">
            <?php endif; ?>

            <div class="admin-form-grid">
                <label class="admin-field admin-field-wide">
                    <span>Heading / Title</span>
                    <input class="textbox" type="text" name="title" maxlength="160" value="<?= pmBlogFormValue($post, 'title') ?>" required>
                </label>

                <div class="admin-field admin-field-wide">
                    <span>Mood</span>
                    <?php pmRenderMoodSelector($currentMood); ?>
                </div>

                <label class="admin-field blog-published-toggle admin-field-wide">
                    <span>Publishing</span>
                    <label class="blog-checkbox-row">
                        <input type="checkbox" name="is_published" value="1" <?= pmBlogPublishedChecked($post) ?>>
                        <span>Published</span>
                    </label>
                </label>

                <div class="admin-field admin-field-wide">
                    <span>Blog Post</span>

                    <div class="rich-editor-toolbar" data-toolbar-for="<?= pmEscape($editorId) ?>">
                        <button type="button" data-command="formatBlock" data-value="h2">H2</button>
                        <button type="button" data-command="formatBlock" data-value="h3">H3</button>
                        <button type="button" data-command="formatBlock" data-value="p">P</button>
                        <span class="rich-editor-divider"></span>
                        <button type="button" data-command="bold"><strong>B</strong></button>
                        <button type="button" data-command="italic"><em>I</em></button>
                        <button type="button" data-command="underline"><u>U</u></button>
                        <span class="rich-editor-divider"></span>
                        <button type="button" data-command="insertUnorderedList">• List</button>
                        <button type="button" data-command="insertOrderedList">1. List</button>
                        <button type="button" data-command="formatBlock" data-value="blockquote">Quote</button>
                        <span class="rich-editor-divider"></span>
                        <button type="button" data-command="justifyLeft">Left</button>
                        <button type="button" data-command="justifyCenter">Center</button>
                        <button type="button" data-command="justifyRight">Right</button>
                        <span class="rich-editor-divider"></span>
                        <button type="button" data-command="createLink">Link</button>
                        <button type="button" data-command="removeFormat">Clear</button>
                    </div>

                    <input type="hidden" id="<?= pmEscape($editorId) ?>" name="body_html" value="<?= pmBlogFormValue($post, 'body_html') ?>">

                    <div class="rich-editor blog-post-editor" contenteditable="true" data-rich-editor data-input="<?= pmEscape($editorId) ?>" aria-label="Blog post content editor"></div>
                </div>

                <div class="admin-field admin-field-wide">
                    <span>Operating Systems / Platforms</span>
                    <?php pmRenderBlogOsSelector($selectedOsTags); ?>
                </div>

                <div class="admin-field admin-field-wide">
                    <span>Technology Discussed</span>
                    <div class="blog-tech-selector tech-icon-selector-grouped">
                        <?php foreach ($categories as $categoryKey => $categoryLabel): ?>
                            <?php $categoryItems = array_filter($catalogue, static fn (array $tech): bool => $tech['category'] === $categoryKey); ?>
                            <?php if ($categoryItems === []): ?><?php continue; ?><?php endif; ?>
                            <section class="tech-selector-group">
                                <h5><?= pmEscape($categoryLabel) ?></h5>
                                <div class="tech-selector-grid">
                                    <?php foreach ($categoryItems as $tech): ?>
                                        <?php
                                        $label = (string) $tech['label'];
                                        $icon = pmTechIconPath($tech['icon'] !== null ? (string) $tech['icon'] : null);
                                        $checked = in_array($label, $selectedTechTags, true) ? 'checked' : '';
                                        ?>
                                        <label class="blog-tech-option tech-choice">
                                            <input type="checkbox" name="tech_tags[]" value="<?= pmEscape($label) ?>" <?= $checked ?>>
                                            <img src="<?= pmEscape($icon) ?>" alt="" loading="lazy">
                                            <span><?= pmEscape($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>

                <label class="admin-field">
                    <span>YouTube URL</span>
                    <input class="textbox" type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= pmBlogFormValue($post, 'youtube_url') ?>">
                </label>

                <label class="admin-field">
                    <span>Image URL</span>
                    <input class="textbox" type="url" name="image_url" placeholder="https://example.com/image.jpg" value="<?= pmBlogFormValue($post, 'image_url') ?>">
                </label>
            </div>

            <div class="admin-actions">
                <button type="submit"><?= pmEscape($actionLabel) ?></button>
            </div>
        </form>
    </article>
    <?php
}
?>

<section class="manage-blog-page">
    <div class="manage-projects-header">
        <div>
            <p class="admin-kicker">Admin Portal</p>
            <h1 class="admin-page-title">Manage Blog</h1>
            <p class="admin-page-subtitle">
                Create, edit, publish, and remove Portfolio Manager blog posts.
            </p>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <p class="contact-flash contact-flash-<?= pmEscape($flash[0]) ?>" role="alert"><?= pmEscape($flash[1]) ?></p>
    <?php endif; ?>

    <div class="manage-blog-layout">
        <aside class="project-admin-sidebar blog-admin-sidebar">
            <div class="admin-panel-heading">
                <h2>Blog Posts</h2>
                <p>Select a post to edit it.</p>
            </div>

            <div class="project-admin-list">
                <?php if ($posts === []): ?>
                    <p class="blog-admin-empty">No blog posts yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($posts as $postItem): ?>
                            <?php
                            $isActive = ((int) $postItem['id'] === $editId) ? 'active' : '';
                            $publishLabel = ((int) $postItem['is_published'] === 1) ? 'Published' : 'Draft';
                            ?>
                            <li>
                                <a class="blog-admin-post-link <?= $isActive ?>" href="index.php?page=manage-blog&edit_id=<?= (int) $postItem['id'] ?>">
                                    <strong><?= pmEscape((string) $postItem['title']) ?></strong>
                                    <span><?= pmEscape(pmAdminFormatDate((string) $postItem['created_at'])) ?> · <?= pmEscape($publishLabel) ?></span>
                                </a>

                                <form class="blog-delete-form" action="src/inc/blog-handler.php" method="post" onsubmit="return confirm('Delete this blog post?');">
                                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $postItem['id'] ?>">
                                    <button type="submit" class="blog-delete-button">Delete</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>

        <section class="project-admin-workspace">
            <?php if ($editingPost !== null): ?>
                <?php pmRenderBlogAdminForm('update', $editingPost); ?>
            <?php endif; ?>

            <?php pmRenderBlogAdminForm('add', null); ?>
        </section>
    </div>
</section>

<script src="src/js/blogeditor.js?v=<?= rawurlencode(PM_VERSION) ?>" defer></script>
