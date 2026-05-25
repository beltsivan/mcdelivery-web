<?php
include('config/db.php');
include('includes/header.php');

if (!isset($_SESSION['Cust_Id'])) {
    echo '<main class="main-container" style="padding:60px 0;text-align:center;"><h1>McDonalds Philippine</h1>
    <h1>Hungry for your favorites?</h1><h1>Log in to view your order history</h1><h1>&</h1>
    <h1>track current deliveries.</h1><a href="index.php" style="display:inline-block;background:#FFBC0D;padding:14px 36px;border-radius:30px;font-weight:bold;text-decoration:none;color:#292929;margin-top:16px;">Go Home</a></main>';
    include('includes/footer.php');
    exit;
}

require_once('includes/cart.php');

$custId = $_SESSION['Cust_Id'];
$orders = mcd_get_customer_orders(null, $custId);
?>
<style>
.orders-page { padding: 40px 0; }
.orders-page h1 { margin: 0 0 8px; color: #292929; }
.orders-page .subtitle { color: #777; margin-bottom: 32px; }
.order-card { background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border-left: 5px solid #FFBC0D; }
.order-card.preparing { border-left-color: #007bff; }
.order-card.ready { border-left-color: #28a745; }
.order-card.completed { border-left-color: #6c757d; }
.order-card.cancelled { border-left-color: #dc3545; }
.order-card-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; }
.order-card-header h3 { margin: 0; font-size: 17px; color: #292929; }
.order-date { color: #888; font-size: 13px; }
.ord-status { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; }
.ord-status.Pending { background: #fff3cd; color: #856404; }
.ord-status.Preparing { background: #cce5ff; color: #004085; }
.ord-status.Ready { background: #d4edda; color: #155724; }
.ord-status.Completed { background: #e2e3e5; color: #383d41; }
.ord-items { margin-top: 14px; border-top: 1px solid #f0f0f0; padding-top: 12px; }
.ord-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 14px; }
.ord-item-img { width: 40px; height: 40px; object-fit: contain; border-radius: 6px; margin-right: 12px; background: #f8f8f8; }
.ord-item-left { display: flex; align-items: center; }
.ord-item-name { color: #333; }
.ord-item-qty { color: #888; font-size: 12px; }
.ord-total-row { display: flex; justify-content: space-between; padding: 10px 0 0; margin-top: 10px; border-top: 1px solid #eee; font-weight: bold; }
.ord-timeline { margin-top: 14px; padding-top: 12px; border-top: 1px solid #f0f0f0; }
.ord-timeline h4 { margin: 0 0 8px; font-size: 14px; color: #555; }
.timeline-item { font-size: 13px; color: #666; padding: 3px 0; display: flex; justify-content: space-between; }
.prep-info { background: #e8f5e9; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; color: #2e7d32; display: inline-block; margin-top: 6px; }
.ord-payment-info { display: flex; justify-content: space-between; align-items: center; padding: 10px 0 0; margin-top: 8px; border-top: 1px solid #eee; font-size: 14px; color: #555; }
.pay-status { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
.pay-status.done { background: #d4edda; color: #155724; }
.pay-status.pending { background: #fff3cd; color: #856404; }
.empty-orders { text-align: center; padding: 70px 20px; color: #888; }
.empty-orders h3 { color: #555; }
</style>

<main class="main-container orders-page">
    <h1>My Orders</h1>
    <p class="subtitle">Track your McDelivery orders in real-time.</p>

    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <h3>No orders yet</h3>
            <p>Place your first order from the menu!</p>
            <a href="menu.php" style="display:inline-block;background:#FFBC0D;padding:14px 36px;border-radius:30px;font-weight:bold;text-decoration:none;color:#292929;">Browse Menu</a>
        </div>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <div class="order-card <?php echo strtolower($order['Order_Status']); ?>">
            <div class="order-card-header">
                <div>
                    <h3>Order #<?php echo $order['Order_Id']; ?></h3>
                    <div class="order-date"><?php echo date('F d, Y - h:i A', strtotime($order['Order_OrderDate'])); ?></div>
                </div>
                <div style="text-align:right;">
                    <span class="ord-status <?php echo $order['Order_Status']; ?>"><?php echo htmlspecialchars($order['Order_Status']); ?></span>
                    <?php if (!empty($order['Order_PrepTime']) && $order['Order_Status'] === 'Preparing'): ?>
                        <div class="prep-info">&#9200; Est. <?php echo (int) $order['Order_PrepTime']; ?> min preparation</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($order['Brnch_Name'])): ?>
                <div style="font-size:13px;color:#666;margin-top:10px;padding:8px 12px;background:#fff8e1;border-radius:8px;display:inline-block;">
                    &#127963; <?php echo htmlspecialchars($order['Brnch_Name']); ?>
                    <?php
                    $bAddr = [];
                    if (!empty($order['Brnch_Street'])) $bAddr[] = $order['Brnch_Street'];
                    if (!empty($order['Brnch_City'])) $bAddr[] = $order['Brnch_City'];
                    if ($bAddr) echo ' - ' . htmlspecialchars(implode(', ', $bAddr));
                    ?>
                </div>
            <?php endif; ?>

            <div class="ord-items">
                <?php foreach ($order['items'] as $item): ?>
                    <div class="ord-item">
                        <div class="ord-item-left">
                            <img class="ord-item-img" src="<?php echo mcd_normalize_image_path($item['Menu_ImageURL']); ?>" alt="">
                            <div>
                                <div class="ord-item-name"><?php echo htmlspecialchars($item['Menu_Name']); ?></div>
                                <div class="ord-item-qty">Qty: <?php echo (int) $item['OrderItem_Quantity']; ?></div>
                            </div>
                        </div>
                        <span>₱<?php echo number_format($item['OrderItem_Total'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="ord-total-row">
                <span>Total</span>
                <span>₱<?php echo number_format($order['Order_TotalAmount'], 2); ?></span>
            </div>

            <?php
            $paymentInfo = mcd_get_payment_info(null, $order['Order_Id']);
            ?>
            <?php if ($paymentInfo): ?>
                <div class="ord-payment-info">
                    <span>Payment: <strong><?php echo htmlspecialchars($paymentInfo['Pay_PaymentType']); ?></strong></span>
                    <span class="pay-status <?php echo strtolower($paymentInfo['Pay_PaymentStatus']); ?>">
                        <?php echo htmlspecialchars($paymentInfo['Pay_PaymentStatus']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php
            // Extract timeline from embedded deliveryStatus
            $timelineItems = $order['deliveryStatus'] ?? [];
            ?>
            <?php if (!empty($timelineItems)): ?>
                <div class="ord-timeline">
                    <h4>Status Timeline</h4>
                    <?php foreach ($timelineItems as $tl): ?>
                        <div class="timeline-item">
                            <span><?php echo htmlspecialchars($tl['Dlvry_StatusUpdate'] ?? ''); ?></span>
                            <span><?php echo isset($tl['Dlvry_DateTime']) ? date('h:i A - M d', strtotime($tl['Dlvry_DateTime'])) : ''; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</main>

<?php include('includes/footer.php'); ?>
