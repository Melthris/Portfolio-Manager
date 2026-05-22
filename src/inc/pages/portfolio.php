<?php
/**
 * Public Portfolio Manager portfolio page.
 *
 * This page intentionally uses the the newer portfolio class structure so
 * the imported CSS behaves correctly, while keeping the wording generic for a
 * public Portfolio Manager installation.
 */

declare(strict_types=1);
?>
<section class="portfolio-page">
    <div class="portfolio-header">
        <p class="admin-kicker">Selected Work</p>
        <h1 class="portfolio-page-title">Portfolio</h1>
        <p class="portfolio-page-subtitle">
            A collection of software, web, automation, and experimental projects added to this Portfolio Manager installation.
        </p>
    </div>

    <section class="portfolio-filter-panel" aria-label="Portfolio filters">
        <div class="portfolio-filter-group">
            <label for="portfolioYearFilter">Year</label>
            <select id="portfolioYearFilter" class="portfolio-filter-select">
                <option value="all">All years</option>
            </select>
        </div>

        <div class="portfolio-filter-group">
            <label for="portfolioTechFilter">Technology</label>
            <select id="portfolioTechFilter" class="portfolio-filter-select">
                <option value="all">All tech</option>
            </select>
        </div>

        <button type="button" id="portfolioResetFilters" class="portfolio-reset-button">
            Reset Filters
        </button>

        <p id="portfolioFilterSummary" class="portfolio-filter-summary">
            Loading projects...
        </p>
    </section>

    <section id="projects" class="project-list" aria-live="polite"></section>
</section>
<script src="src/js/projectscript.js?v=<?= rawurlencode(PM_VERSION) ?>" defer></script>
