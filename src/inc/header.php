<?php
$isLoggedIn = isset($_SESSION['UserData']['Username']);
$username = $isLoggedIn ? htmlspecialchars($_SESSION['UserData']['Username']) : null;
require_once __DIR__ . '/version.php';
?>

<div class="banner">
    <div class="top-float-name">
    <h6>Portfolio Manager (v<?php echo SITE_VERSION; ?>)</h6>
    </div>
    
    <div class="top-float">
        <a class="header-links" href="index.php?page=home">Home</a>
        <span>|</span>
        <a class="header-links" href="index.php?page=portfolio">Portfolio</a>
        <span>|</span>
        <a class="header-links" href="index.php?page=adminlogonportal">Login</a>
        <span>|</span>
        
        <?php if ($isLoggedIn): ?>
            <a class="header-links" href="index.php?page=manage-portfolio">Manage Portfolio</a>
            <span>|</span>
            <a class="header-links" href="index.php?page=logout">Logout</a>
        <?php endif; ?>
    </div>
            
    <?php if ($isLoggedIn): ?>
        <div class="greeting">
            <p>Hello, <?= $username ?>!</p>
        </div>
    <?php endif; ?>
</div>