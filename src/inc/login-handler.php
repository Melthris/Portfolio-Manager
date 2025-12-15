<?php
session_start();

if (isset($_POST['Submit'])) {
    $logins = array('John.Doe' => 'Password123!');
    #echo getenv('PRIVATE_LOGIN_PASSWORD');
    #echo getenv('PRIVATE_LOGIN_USERNAME');
    $Username = isset($_POST['Username']) ? $_POST['Username'] : '';
    $Password = isset($_POST['Password']) ? $_POST['Password'] : '';

    if (isset($logins[$Username]) && $logins[$Username] == $Password) {
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