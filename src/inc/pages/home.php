<?php
/**
 * Public home page.
 *
 * The title, subheading, and supporting body text are intentionally pulled from
 * Site Management settings so Portfolio Manager users can personalise the home
 * page without editing PHP templates directly.
 */

declare(strict_types=1);
?>
<div class="container">
    <div class="main-content">
        <h1 class="title"><?= pmEscape(pmAppName()) ?></h1>
        <h4><?= pmEscape(pmHomeSubheading()) ?></h4>
        <div class="void-space"></div>
        <h4><?= nl2br(pmEscape(pmHomeBodyText())) ?></h4>
    </div>
</div>
