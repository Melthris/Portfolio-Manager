<?php
/**
 * Blog Management handler.
 *
 * The Blog Manager now generates slugs and excerpts automatically so the admin
 * form only needs the title, mood, publishing state, blog post content, tags,
 * platform pins, and optional media URLs.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmRequirePermission('can_manage_blog');

/**
 * Builds the public preview text from the submitted rich-text blog body.
 *
 * The generated excerpt is stored for quick listing-page rendering, but it is
 * derived from the actual blog post content so the admin does not need a second
 * redundant summary field.
 *
 * @param string $bodyHtml Submitted rich-text HTML.
 * @param int $limit Maximum characters to keep.
 * @return string Plain-text excerpt.
 */
function pmBuildBlogExcerpt(string $bodyHtml, int $limit = 220): string
{
    $plainText = trim(html_entity_decode(strip_tags($bodyHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $plainText = preg_replace('/\s+/', ' ', $plainText) ?? $plainText;

    if (strlen($plainText) <= $limit) {
        return $plainText;
    }

    return rtrim(substr($plainText, 0, max(0, $limit - 3))) . '...';
}

/**
 * Creates a unique blog slug from a title.
 *
 * Blog authors no longer manually edit slugs. This helper prevents duplicate
 * titles from crashing the unique slug column by appending a numeric suffix
 * when required.
 *
 * @param string $title Blog post title.
 * @param int $ignoreId Existing post ID to ignore while editing.
 * @return string Unique slug.
 */
function pmBuildUniqueBlogSlug(string $title, int $ignoreId = 0): string
{
    $baseSlug = pmSlugify($title);
    $slug = $baseSlug !== '' ? $baseSlug : 'blog-post';
    $candidate = $slug;
    $counter = 2;

    while (true) {
        $stmt = pmDb()->prepare('SELECT id FROM blog_posts WHERE slug = :slug AND id != :id LIMIT 1');
        $stmt->execute([':slug' => $candidate, ':id' => $ignoreId]);

        if ($stmt->fetch() === false) {
            return $candidate;
        }

        $candidate = $slug . '-' . $counter;
        $counter++;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    pmRedirect('../../index.php?page=manage-blog&status=invalid');
}

$action = pmString($_POST['action'] ?? 'save');

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    pmDb()->prepare('DELETE FROM blog_posts WHERE id = :id')->execute([':id' => $id]);
    pmRedirect('../../index.php?page=manage-blog&status=deleted');
}

$title = pmString($_POST['title'] ?? '');
$body = (string) ($_POST['body_html'] ?? '');
$id = (int) ($_POST['id'] ?? 0);
$isPublished = isset($_POST['is_published']) ? 1 : 0;
$slug = pmBuildUniqueBlogSlug($title, $id);
$excerpt = pmBuildBlogExcerpt($body);

if ($title === '') {
    pmRedirect('../../index.php?page=manage-blog&status=missing-title');
}

if ($id > 0) {
    $stmt = pmDb()->prepare(<<<'SQL'
        UPDATE blog_posts
        SET title = :title, slug = :slug, excerpt = :excerpt, body_html = :body_html, mood = :mood,
            tech_tags = :tech_tags, os_tags = :os_tags, image_url = :image_url, youtube_url = :youtube_url,
            is_published = :is_published, published_at = CASE WHEN :is_published = 1 THEN COALESCE(published_at, CURRENT_TIMESTAMP) ELSE published_at END,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    SQL);
    $stmt->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':excerpt' => $excerpt,
        ':body_html' => $body,
        ':mood' => pmString($_POST['mood'] ?? $_POST['mood_key'] ?? 'focused'),
        ':tech_tags' => json_encode(pmStringList($_POST['tech_tags'] ?? [])),
        ':os_tags' => json_encode(pmStringList($_POST['os_tags'] ?? [])),
        ':image_url' => pmString($_POST['image_url'] ?? ''),
        ':youtube_url' => pmString($_POST['youtube_url'] ?? ''),
        ':is_published' => $isPublished,
        ':id' => $id,
    ]);
} else {
    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO blog_posts (title, slug, excerpt, body_html, mood, tech_tags, os_tags, image_url, youtube_url, is_published, published_at, author_user_id)
        VALUES (:title, :slug, :excerpt, :body_html, :mood, :tech_tags, :os_tags, :image_url, :youtube_url, :is_published, CASE WHEN :is_published = 1 THEN CURRENT_TIMESTAMP ELSE NULL END, :author_user_id)
    SQL);
    $stmt->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':excerpt' => $excerpt,
        ':body_html' => $body,
        ':mood' => pmString($_POST['mood'] ?? $_POST['mood_key'] ?? 'focused'),
        ':tech_tags' => json_encode(pmStringList($_POST['tech_tags'] ?? [])),
        ':os_tags' => json_encode(pmStringList($_POST['os_tags'] ?? [])),
        ':image_url' => pmString($_POST['image_url'] ?? ''),
        ':youtube_url' => pmString($_POST['youtube_url'] ?? ''),
        ':is_published' => $isPublished,
        ':author_user_id' => pmCurrentUserId(),
    ]);
}

pmRedirect('../../index.php?page=manage-blog&status=saved');
