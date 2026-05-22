<?php
/**
 * Public blog listing page.
 *
 * This page deliberately uses the same visual shell as the working Portfolio
 * page: a header card, filter panel, and stacked content cards. That keeps the
 * Blog page visually aligned with the imported newer CSS while retaining
 * generic Portfolio Manager function names and copy.
 */

declare(strict_types=1);

$allPosts = pmGetBlogPosts(false);
$selectedYear = pmString($_GET['year'] ?? 'all', 'all');
$selectedMonth = pmString($_GET['month'] ?? 'all', 'all');
$selectedTech = pmString($_GET['tech'] ?? 'all', 'all');

/**
 * Formats a database date into the compact public blog display format.
 *
 * @param string $date Raw database date value.
 * @return string Human-readable date.
 */
function pmBlogPageFormatDate(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('M j, Y', $timestamp);
}

/**
 * Returns a clean excerpt for a blog listing card.
 *
 * @param array<string, mixed> $post Blog post row.
 * @return string Short text excerpt.
 */
function pmBlogPageExcerpt(array $post): string
{
    $excerpt = trim((string) ($post['excerpt'] ?? ''));

    if ($excerpt === '') {
        $excerpt = trim(html_entity_decode(strip_tags((string) ($post['body_html'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $excerpt = preg_replace('/\s+/', ' ', $excerpt) ?? $excerpt;
    }

    if (strlen($excerpt) <= 180) {
        return $excerpt;
    }

    return rtrim(substr($excerpt, 0, 177)) . '...';
}

/**
 * Extracts selected technology tags from a blog post row.
 *
 * @param array<string, mixed> $post Blog post row.
 * @return array<int, string> Clean technology tag labels.
 */
function pmBlogPageTechTags(array $post): array
{
    $decoded = json_decode((string) ($post['tech_tags'] ?? '[]'), true);

    if (!is_array($decoded)) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $decoded), static fn (string $tag): bool => trim($tag) !== ''));
}


/**
 * Extracts selected operating system/platform tags from a blog post row.
 *
 * @param array<string, mixed> $post Blog post row.
 * @return array<int, string> Clean platform tag labels.
 */
function pmBlogPageOsTags(array $post): array
{
    $decoded = json_decode((string) ($post['os_tags'] ?? '[]'), true);

    if (!is_array($decoded)) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $decoded), static fn (string $tag): bool => trim($tag) !== ''));
}

/**
 * Returns every year represented by the available blog posts.
 *
 * @param array<int, array<string, mixed>> $posts Blog posts.
 * @return array<int, string> Descending year list.
 */
function pmBlogPageAvailableYears(array $posts): array
{
    $years = [];

    foreach ($posts as $post) {
        $date = (string) ($post['published_at'] ?? $post['created_at'] ?? '');
        $timestamp = strtotime($date);

        if ($timestamp !== false) {
            $years[] = date('Y', $timestamp);
        }
    }

    $years = array_values(array_unique($years));
    rsort($years);

    return $years;
}

/**
 * Returns every month represented by the available blog posts.
 *
 * @param array<int, array<string, mixed>> $posts Blog posts.
 * @return array<string, string> Month key to month label map.
 */
function pmBlogPageAvailableMonths(array $posts): array
{
    $months = [];

    foreach ($posts as $post) {
        $date = (string) ($post['published_at'] ?? $post['created_at'] ?? '');
        $timestamp = strtotime($date);

        if ($timestamp !== false) {
            $months[date('Y-m', $timestamp)] = date('F Y', $timestamp);
        }
    }

    krsort($months);

    return $months;
}

/**
 * Returns every technology tag represented by the available blog posts.
 *
 * @param array<int, array<string, mixed>> $posts Blog posts.
 * @return array<int, string> Sorted technology labels.
 */
function pmBlogPageAvailableTech(array $posts): array
{
    $tags = [];

    foreach ($posts as $post) {
        foreach (pmBlogPageTechTags($post) as $tag) {
            $tags[] = $tag;
        }
    }

    $tags = array_values(array_unique($tags));
    sort($tags, SORT_NATURAL | SORT_FLAG_CASE);

    return $tags;
}

/**
 * Filters public blog posts using the selected year, month, and technology.
 *
 * @param array<int, array<string, mixed>> $posts Blog posts.
 * @param string $year Selected year or "all".
 * @param string $month Selected month key or "all".
 * @param string $tech Selected technology tag or "all".
 * @return array<int, array<string, mixed>> Filtered posts.
 */
function pmBlogPageFilterPosts(array $posts, string $year, string $month, string $tech): array
{
    return array_values(array_filter($posts, static function (array $post) use ($year, $month, $tech): bool {
        $date = (string) ($post['published_at'] ?? $post['created_at'] ?? '');
        $timestamp = strtotime($date);

        if ($year !== 'all' && $timestamp !== false && date('Y', $timestamp) !== $year) {
            return false;
        }

        if ($month !== 'all' && $timestamp !== false && date('Y-m', $timestamp) !== $month) {
            return false;
        }

        if ($tech !== 'all' && !in_array($tech, pmBlogPageTechTags($post), true)) {
            return false;
        }

        return true;
    }));
}

/**
 * Builds a pagination URL while preserving selected filters.
 *
 * @param int $pageNumber Page number.
 * @param string $year Selected year.
 * @param string $month Selected month.
 * @param string $tech Selected technology.
 * @return string Relative pagination URL.
 */
function pmBlogPagePaginationUrl(int $pageNumber, string $year, string $month, string $tech): string
{
    $params = ['page' => 'blog', 'blog_page' => $pageNumber];

    if ($year !== 'all') {
        $params['year'] = $year;
    }

    if ($month !== 'all') {
        $params['month'] = $month;
    }

    if ($tech !== 'all') {
        $params['tech'] = $tech;
    }

    return 'index.php?' . http_build_query($params);
}


/**
 * Renders selected platform tags for a blog card.
 *
 * @param array<string, mixed> $post Blog post row.
 * @return string Rendered HTML.
 */
function pmBlogPageRenderOsTags(array $post): string
{
    $tags = pmBlogPageOsTags($post);

    if ($tags === []) {
        return '';
    }

    $catalogue = pmOsPlatformCatalogue();
    $lookup = [];

    foreach ($catalogue as $platform) {
        $lookup[(string) $platform['label']] = (string) $platform['icon'];
    }

    $html = '<div class="blog-os-tags">';

    foreach ($tags as $tag) {
        $iconPath = $lookup[$tag] ?? 'src/icons/unknown.svg';
        $html .= '<span class="blog-os-tag"><img src="' . pmEscape($iconPath) . '" alt="">' . pmEscape($tag) . '</span>';
    }

    return $html . '</div>';
}

/**
 * Renders selected technology tags for a blog card.
 *
 * @param array<string, mixed> $post Blog post row.
 * @return string Rendered HTML.
 */
function pmBlogPageRenderTechTags(array $post): string
{
    $tags = pmBlogPageTechTags($post);

    if ($tags === []) {
        return '';
    }

    $catalogue = pmTechCatalogue();
    $html = '<div class="blog-tech-tags">';

    foreach ($tags as $tag) {
        $key = pmTechKeyFromLabel($tag);
        $icon = $catalogue[$key]['icon'] ?? null;
        $iconPath = pmTechIconPath($icon !== null ? (string) $icon : null);
        $html .= '<span class="blog-tech-tag"><img src="' . pmEscape($iconPath) . '" alt="">' . pmEscape($tag) . '</span>';
    }

    return $html . '</div>';
}

/**
 * Renders the mood badge for a blog card.
 *
 * @param array<string, mixed> $post Blog post row.
 * @return string Rendered HTML.
 */
function pmBlogPageRenderMoodBadge(array $post): string
{
    $mood = pmString($post['mood'] ?? 'focused', 'focused');

    // Keep compatibility with older staged posts saved as "released".
    if ($mood === 'released') {
        $mood = 'shipped';
    }

    $label = ucfirst(str_replace('-', ' ', $mood));
    $icon = 'src/icons/moods/' . $mood . '.svg';

    if (!is_file(dirname(__DIR__, 2) . '/icons/moods/' . $mood . '.svg')) {
        return '<span class="blog-mood-badge"><span class="blog-mood-emoji">•</span>' . pmEscape($label) . '</span>';
    }

    return '<span class="blog-mood-badge"><img src="' . pmEscape($icon) . '" alt="">' . pmEscape($label) . '</span>';
}

$years = pmBlogPageAvailableYears($allPosts);
$months = pmBlogPageAvailableMonths($allPosts);
$techOptions = pmBlogPageAvailableTech($allPosts);
$filteredPosts = pmBlogPageFilterPosts($allPosts, $selectedYear, $selectedMonth, $selectedTech);
$postsPerPage = 15;
$totalFilteredPosts = count($filteredPosts);
$totalPages = max(1, (int) ceil($totalFilteredPosts / $postsPerPage));
$currentPage = max(1, min((int) ($_GET['blog_page'] ?? 1), $totalPages));
$posts = array_slice($filteredPosts, ($currentPage - 1) * $postsPerPage, $postsPerPage);
?>

<section class="blog-page">
    <div class="blog-header">
        <p class="admin-kicker">Notes &amp; Updates</p>
        <h1 class="blog-page-title">Blog</h1>
        <p class="blog-page-subtitle">
            Development notes, project updates, technical thoughts, and Portfolio Manager progress logs.
        </p>
    </div>

    <form class="blog-filter-panel" method="get" action="index.php">
        <input type="hidden" name="page" value="blog">

        <label class="portfolio-filter-group">
            <span>Year</span>
            <select class="portfolio-filter-select" name="year">
                <option value="all">All years</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= pmEscape($year) ?>" <?= $selectedYear === $year ? 'selected' : '' ?>><?= pmEscape($year) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="portfolio-filter-group">
            <span>Date</span>
            <select class="portfolio-filter-select" name="month">
                <option value="all">All dates</option>
                <?php foreach ($months as $monthKey => $monthLabel): ?>
                    <option value="<?= pmEscape($monthKey) ?>" <?= $selectedMonth === $monthKey ? 'selected' : '' ?>><?= pmEscape($monthLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="portfolio-filter-group">
            <span>Technology</span>
            <select class="portfolio-filter-select" name="tech">
                <option value="all">All tech</option>
                <?php foreach ($techOptions as $tech): ?>
                    <option value="<?= pmEscape($tech) ?>" <?= $selectedTech === $tech ? 'selected' : '' ?>><?= pmEscape($tech) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit" class="portfolio-reset-button">Apply Filters</button>
        <a class="blog-filter-reset" href="index.php?page=blog">Reset</a>

        <p class="portfolio-filter-summary">
            <?= $totalFilteredPosts === 0 ? 'Showing 0 posts.' : 'Showing ' . count($posts) . ' of ' . $totalFilteredPosts . ' matching post' . ($totalFilteredPosts === 1 ? '.' : 's.') ?>
        </p>
    </form>

    <?php if ($posts === []): ?>
        <article class="blog-empty-state">
            <h2>No matching posts</h2>
            <p>No blog posts match the selected filters yet.</p>
        </article>
    <?php else: ?>
        <section class="blog-grid">
            <?php foreach ($posts as $index => $post): ?>
                <article class="blog-card" style="--i: <?= (int) $index ?>;">
                    <div class="blog-card-meta">
                        <span><?= pmEscape(pmBlogPageFormatDate((string) ($post['published_at'] ?? $post['created_at'] ?? ''))) ?></span>
                        <span>By <?= pmEscape(pmUserDisplayName($post)) ?></span>
                        <?= pmBlogPageRenderMoodBadge($post) ?>
                    </div>

                    <h2>
                        <a href="index.php?page=blog-post&amp;slug=<?= rawurlencode((string) $post['slug']) ?>">
                            <?= pmEscape((string) $post['title']) ?>
                        </a>
                    </h2>

                    <?= pmBlogPageRenderOsTags($post) ?>
                    <?= pmBlogPageRenderTechTags($post) ?>

                    <p><?= pmEscape(pmBlogPageExcerpt($post)) ?></p>

                    <a class="blog-read-more" href="index.php?page=blog-post&amp;slug=<?= rawurlencode((string) $post['slug']) ?>">Read post</a>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="blog-pagination" aria-label="Blog pagination">
                <?php if ($currentPage > 1): ?>
                    <a class="blog-pagination-link" href="<?= pmEscape(pmBlogPagePaginationUrl($currentPage - 1, $selectedYear, $selectedMonth, $selectedTech)) ?>">Previous 15</a>
                <?php else: ?>
                    <span class="blog-pagination-link blog-pagination-disabled">Previous 15</span>
                <?php endif; ?>

                <div class="blog-pagination-pages">
                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                        <?php if ($pageNumber === $currentPage): ?>
                            <span class="blog-pagination-number active"><?= $pageNumber ?></span>
                        <?php else: ?>
                            <a class="blog-pagination-number" href="<?= pmEscape(pmBlogPagePaginationUrl($pageNumber, $selectedYear, $selectedMonth, $selectedTech)) ?>"><?= $pageNumber ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="blog-pagination-link" href="<?= pmEscape(pmBlogPagePaginationUrl($currentPage + 1, $selectedYear, $selectedMonth, $selectedTech)) ?>">Next 15</a>
                <?php else: ?>
                    <span class="blog-pagination-link blog-pagination-disabled">Next 15</span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
