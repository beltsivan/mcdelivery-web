<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Cust_Id'])) {
    header('Location: login.php');
    exit;
}

require_once('includes/cart.php');
require_once('config/db.php');

$custId = (int) $_SESSION['Cust_Id'];
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Cash on Delivery';

$orderId = mcd_checkout($conn, $custId, $paymentMethod);

if ($orderId) {
    $_SESSION['order_success'] = $orderId;
    header('Location: order_success.php?order_id=' . $orderId);
} else {
    header('Location: menu.php?bag=1&checkout_error=1');
}
exit;
