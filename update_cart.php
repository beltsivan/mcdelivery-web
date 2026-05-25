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

$custId = $_SESSION['Cust_Id'];
$cartItemId = isset($_POST['cart_item_id']) ? $_POST['cart_item_id'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($cartItemId !== '' && in_array($action, ['increase', 'decrease'])) {
    mcd_update_cart_quantity(null, $custId, $cartItemId, $action);
}

$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'menu.php';
$separator = (strpos($redirect, '?') !== false) ? '&' : '?';
header('Location: ' . $redirect . $separator . 'bag=1');
exit;
