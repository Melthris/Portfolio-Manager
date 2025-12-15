<?php
session_start();

require_once __DIR__ . '/logindetails.php'; // Calling in login details

if (isset($_POST['Submit'])) {
    $logins = [ADMIN_USERNAME => ADMIN_PASSWORD];

    $Username = $_POST['Username'] ?? '';
    $Password = $_POST['Password'] ?? '';

    if (isset($logins[$Username]) && $logins[$Username] === $Password) {
        $_SESSION['UserData']['Username'] = $Username;

        // Redirect BEFORE any output
        header("Location: ../../index.php?page=manage-portfolio");
        exit;
    } else {
        // Optional: redirect back with an error message
        header("Location: ../../index.php?page=adminlogonportal.php&error=1");
        exit;
    }
}
?>