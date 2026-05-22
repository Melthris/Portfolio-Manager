<?php
/**
 * Portfolio Manager
 *
 * The file but routes through a whitelist so disabled 
 * modules and admin-only pages are protected.
 */

declare(strict_types=1);

require_once __DIR__ . '/src/inc/functions.php';

$rawPage = isset($_GET['page']) ? (string) $_GET['page'] : PM_DEFAULT_PAGE;
$pageForLogout = pmNormalisePageName($rawPage);

if ($pageForLogout === 'logout') {
    pmLogout();
    pmRedirect('index.php?page=' . PM_DEFAULT_PAGE);
}

$route = pmResolvePage($rawPage, pmIsLoggedIn());

if ($route['redirect'] !== null) {
    pmRedirect($route['redirect']);
}

if ($route['is404']) {
    http_response_code(404);
}

$page = $route['page'];
$pageTitle = $route['title'];
$requestedPage = $route['requested'];

include __DIR__ . '/src/inc/meta.php';
include __DIR__ . '/src/inc/header.php';
?>

<main>
    <div class="bg"></div>
    <?php
    $pageFile = __DIR__ . "/src/inc/pages/{$page}.php";

    if (is_file($pageFile)) {
        include $pageFile;
    } else {
        http_response_code(404);
        include __DIR__ . '/src/inc/pages/404.php';
    }
    ?>
</main>

<?php include __DIR__ . '/src/inc/footer.php'; ?>
