<?php
/**
 * Shared document head and browser cache controls.
 */

declare(strict_types=1);

pmStartSession();
pmSendNoCacheHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="icon.ico">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= pmEscape($pageTitle ?? pmPageTitle('Home')) ?></title>
    <link rel="stylesheet" type="text/css" href="src/main.css">
    <link rel="stylesheet" type="text/css" href="src/inc/theme.css.php">
    <script src="src/inc/tech-catalogue.js.php?v=<?= rawurlencode(PM_VERSION) ?>"></script>
    <script src="src/inc/repo-providers.js.php"></script>
    <script src="src/js/repo-providers.js"></script>
    <script src="src/js/projectscript.js"></script>
    <script src="src/js/manageprojects.js"></script>
    <script src="src/js/mobile-filter-selects.js?v=<?= rawurlencode(PM_VERSION) ?>" defer></script>
</head>
<body>
