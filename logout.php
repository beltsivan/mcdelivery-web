<?php
session_start();
session_unset();
session_destroy();

$redirect = 'index.php';

if (isset($_GET['from']) && $_GET['from'] === 'admin') {
    $redirect = 'staff_login.php';
}

header("Location: $redirect");
exit();
?>