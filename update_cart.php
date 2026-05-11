<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Cust_Id'])) {
    header('Location: index.php');
    exit;
}

require_once('includes/cart.php');
require_once('config/db.php');

$custId = (int) $_SESSION['Cust_Id'];
$menuItemId = isset($_POST['menu_item_id']) ? (int) $_POST['menu_item_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($menuItemId > 0 && in_array($action, ['increase', 'decrease'])) {
    mcd_update_cart_quantity($conn, $custId, $menuItemId, $action);
}

$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'menu.php';
header('Location: ' . $redirect . '?bag=1');
exit;
