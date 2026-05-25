<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('config/db.php');
include('includes/header.php');

$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : 0;

$order = null;
if ($orderId && isset($_SESSION['Cust_Id']) && $firebaseInitialized) {
    $db = $firestore->database();
    $orderDoc = $db->collection('orders')->document((string) $orderId)->snapshot();
    if ($orderDoc->exists()) {
        $orderData = $orderDoc->data();
        if (($orderData['Order_Cust_Id'] ?? '') === $_SESSION['Cust_Id']) {
            $order = $orderData;
            $order['Order_Id'] = $orderDoc->id();
        }
    }
}

$orderSuccess = isset($_SESSION['order_success']) ? $_SESSION['order_success'] : null;
unset($_SESSION['order_success']);
?>

<main class="main-container" style="padding: 60px 0; text-align: center;">
    <?php if ($order || $orderSuccess): ?>
        <div style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 18px; padding: 48px 32px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
            <div style="font-size: 64px; margin-bottom: 16px;">&#10003;</div>
            <h1>Order Placed!</h1>
            <p style="color: #555; font-size: 17px; margin: 12px 0 24px;">
                Your order #<?php echo $orderId; ?> has been placed successfully.
            </p>
            <?php if ($order): ?>
                <?php if (!empty($order['Brnch_Name'])): ?>
                <p style="color: #777;font-size:13px;background:#fff8e1;display:inline-block;padding:6px 16px;border-radius:20px;">
                    &#127963; <?php echo htmlspecialchars($order['Brnch_Name'] . ' - ' . ($order['Brnch_Street'] ?? '') . ', ' . ($order['Brnch_City'] ?? '')); ?>
                </p>
                <?php endif; ?>
                <p style="color: #777;">Total: <strong>₱<?php echo number_format($order['Order_TotalAmount'], 2); ?></strong></p>
                <p style="color: #777;">Status: <strong><?php echo htmlspecialchars($order['Order_Status']); ?></strong></p>
            <?php endif; ?>
            <a href="menu.php" style="display: inline-block; background: #FFBC0D; color: #292929; text-decoration: none; padding: 14px 36px; border-radius: 30px; font-weight: bold; margin-top: 20px;">Back to Menu</a>
        </div>
    <?php else: ?>
        <div style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 18px; padding: 48px 32px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
            <h1>Order not found</h1>
            <p style="color: #555;">We could not find this order.</p>
            <a href="menu.php" style="display: inline-block; background: #FFBC0D; color: #292929; text-decoration: none; padding: 14px 36px; border-radius: 30px; font-weight: bold; margin-top: 20px;">Back to Menu</a>
        </div>
    <?php endif; ?>
</main>

<?php include('includes/footer.php'); ?>
