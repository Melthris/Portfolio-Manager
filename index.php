<?php
include 'src/inc/functions.php';
include 'src/inc/meta.php';
include 'src/inc/header.php';
?>

<main>
<div class="bg"></div>
    <?php
    $page = isset($_GET['page']) ? $_GET['page'] : 'home'; // Default page

    $file = "src/inc/pages/{$page}.php";

    if (file_exists($file)) {
        include $file;
    } else {
        include "src/inc/pages/404.php"; // A classic redirect when people try invalid links

    }
    ?>
</main>
<?php
include 'src/inc/footer.php';
?>
