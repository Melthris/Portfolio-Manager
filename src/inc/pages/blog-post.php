<?php
/**
 * Public single blog post page.
 */

declare(strict_types=1);

$post = pmFindBlogPostBySlug(pmString($_GET['slug'] ?? ''));
?>
<?php if ($post === null): ?>
    <section class="page-hero"><h1>Post not found</h1><p>This post does not exist or is not published.</p></section>
<?php else: ?>
    <article class="content-article">
        <h1><?= pmEscape($post['title']) ?></h1>
        <p class="muted">By <?= pmEscape(pmUserDisplayName($post)) ?> · <?= pmEscape($post['published_at'] ?? $post['created_at']) ?></p>
        <?php if (!empty($post['excerpt'])): ?><p class="lead"><?= pmEscape($post['excerpt']) ?></p><?php endif; ?>
        <div class="rich-content"><?= (string) $post['body_html'] ?></div>
    </article>
<?php endif; ?>
