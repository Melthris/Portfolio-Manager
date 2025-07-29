<?php
// I wasn't sure where I should add this function but figured this is probably the best place
// I can call on this function on protected pages such as manage-project. It will return
// as a 404 error page. You also need to include the footer.php file to this as well as the exit
// function will terminate the PHP early and the footer will be cut off.
function rejectIfNotLoggedIn() {
    if (!isset($_SESSION['UserData']['Username'])) {
        include "src/inc/pages/404.php";
        include "src/inc/footer.php";
        exit;
    }
}
?>