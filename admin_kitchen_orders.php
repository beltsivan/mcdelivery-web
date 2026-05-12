<style>
.kitchen-orders { max-width: 1200px; }
.orders-tabs { display: flex; gap: 8px; margin-bottom: 24px; }
.orders-tabs a { text-decoration: none; padding: 10px 22px; border-radius: 25px; font-weight: bold; font-size: 14px; background: #eee; color: #555; }
.orders-tabs a.active { background: #FFBC0D; color: #292929; }
.order-card { background: #fff; border-radius: 14px; padding: 22px; margin-bottom: 18px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border-left: 5px solid #FFBC0D; }
.order-card.preparing { border-left-color: #007bff; }
.order-card.ready { border-left-color: #28a745; }
.order-card.completed { border-left-color: #6c757d; }
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.order-header h3 { margin: 0; font-size: 18px; color: #292929; }
.order-header .order-time { color: #888; font-size: 13px; }
.order-customer { color: #555; font-size: 14px; margin-bottom: 12px; }
.order-customer strong { color: #292929; }
.order-items { border-top: 1px solid #f0f0f0; padding-top: 12px; margin-top: 12px; }
.order-item { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; color: #444; }
.order-actions { margin-top: 16px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.accept-btn { background: #28a745; color: #fff; border: none; padding: 10px 24px; border-radius: 25px; font-weight: bold; cursor: pointer; font-size: 14px; }
.accept-btn:hover { background: #218838; }
.prep-time-input { width: 80px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; text-align: center; }
.prep-time-label { font-size: 13px; color: #555; }
.status-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; }
.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.preparing { background: #cce5ff; color: #004085; }
.status-badge.ready { background: #d4edda; color: #155724; }
.status-badge.completed { background: #e2e3e5; color: #383d41; }
.prep-time-display { background: #e8f5e9; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold; color: #2e7d32; }
.order-payment { display: flex; justify-content: space-between; align-items: center; padding: 8px 0 0; margin-top: 6px; border-top: 1px solid #f0f0f0; font-size: 13px; color: #555; }
.pay-status-label { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
.pay-status-label.done { background: #d4edda; color: #155724; }
.pay-status-label.pending { background: #fff3cd; color: #856404; }
.empty-orders { text-align: center; padding: 60px 20px; color: #888; }
.empty-orders h3 { color: #555; }
.customer-group { background: #fff; border-radius: 14px; margin-bottom: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); overflow: hidden; }
.customer-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 22px; cursor: pointer; transition: 0.2s; border-left: 5px solid #6c757d; }
.customer-header:hover { background: #f9f9f9; }
.customer-header h3 { margin: 0; font-size: 16px; color: #292929; }
.customer-header .cust-meta { color: #888; font-size: 13px; }
.customer-header .expand-icon { font-size: 14px; color: #999; transition: 0.2s; }
.customer-header.open .expand-icon { transform: rotate(180deg); }
.customer-orders { display: none; }
.customer-orders.open { display: block; }
.customer-orders .order-card { margin: 0 14px 14px; border-left-color: #adb5bd; }
</style>

<div class="kitchen-orders">
    <?php
    $currentTab = isset($_GET['order_status']) ? $_GET['order_status'] : 'pending';

    $statusMap = [
        'pending'   => ['Pending'],
        'preparing' => ['Preparing'],
        'ready'     => ['Ready'],
        'all'       => ['Completed'],
    ];

    $tabLabel = [
        'pending'   => 'New Orders',
        'preparing' => 'Preparing',
        'ready'     => 'Ready for Pickup',
        'all'       => 'All Orders',
    ];

    $filter = isset($statusMap[$currentTab]) ? $statusMap[$currentTab] : ['Pending'];

    // Handle accept order
    if (isset($_POST['accept_order'])) {
        $acceptId = (int) $_POST['order_id'];
        $prepTime = (int) $_POST['prep_time'];
        if ($prepTime < 1) $prepTime = 15;
        if (mcd_accept_order($conn, $acceptId, $prepTime)) {
            echo '<div class="alert-success">Order #' . $acceptId . ' accepted! Preparing time: ' . $prepTime . ' mins</div>';
        }
    }

    // Handle status update
    if (isset($_POST['update_status'])) {
        $updateId = (int) $_POST['order_id'];
        $newStatus = $_POST['new_status'];
        if (mcd_update_order_status($conn, $updateId, $newStatus)) {
            echo '<div class="alert-success">Order #' . $updateId . ' updated to ' . $newStatus . '</div>';
        }
    }

    $orders = mcd_get_kitchen_orders($conn, $filter);
    ?>

    <div class="orders-tabs">
        <?php foreach ($tabLabel as $key => $label): ?>
            <a href="admin_dashboard.php?page=orders&order_status=<?php echo $key; ?>"
               class="<?php echo $currentTab === $key ? 'active' : ''; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($currentTab === 'all'): ?>
        <?php
        $customers = [];
        foreach ($orders as $order) {
            $name = $order['Cust_FName'] . ' ' . $order['Cust_LName'];
            $customers[$name][] = $order;
        }
        ?>
        <?php if (empty($customers)): ?>
            <div class="empty-orders"><h3>No completed orders</h3><p>No orders have been completed yet.</p></div>
        <?php endif; ?>
        <?php $idx = 0; foreach ($customers as $custName => $custOrders): $idx++; ?>
            <div class="customer-group">
                <div class="customer-header" onclick="toggleCustomer(<?php echo $idx; ?>)">
                    <div>
                        <h3><?php echo htmlspecialchars($custName); ?></h3>
                        <span class="cust-meta"><?php echo count($custOrders); ?> order(s) &nbsp;|&nbsp; Total: ₱<?php echo number_format(array_sum(array_column($custOrders, 'Order_TotalAmount')), 2); ?></span>
                    </div>
                    <span class="expand-icon">&#9660;</span>
                </div>
                <div class="customer-orders" id="customer-<?php echo $idx; ?>">
                    <?php foreach ($custOrders as $order): ?>
                        <div class="order-card completed">
                            <div class="order-header">
                                <div>
                                    <h3>Order #<?php echo $order['Order_Id']; ?></h3>
                                    <span class="status-badge completed">Completed</span>
                                </div>
                                <div style="text-align:right;">
                                    <div class="order-time"><?php echo date('M d, Y - h:i A', strtotime($order['Order_OrderDate'])); ?></div>
                                </div>
                            </div>

                            <?php if (!empty($order['Add_Street'])): ?>
                                <div class="order-customer" style="font-size:13px;color:#666;">
                                    &#128205; <?php echo htmlspecialchars($order['Add_Street']); ?>, Brgy. <?php echo htmlspecialchars($order['Add_Barangay']); ?>,
                                    <?php echo htmlspecialchars($order['Add_Municipality']); ?>, <?php echo htmlspecialchars($order['Add_City']); ?> <?php echo htmlspecialchars($order['Add_PostalCode']); ?>
                                </div>
                            <?php endif; ?>

                            <?php $paymentInfo = mcd_get_payment_info($conn, $order['Order_Id']); ?>
                            <?php if ($paymentInfo): ?>
                                <div class="order-payment">
                                    <span>Payment: <strong><?php echo htmlspecialchars($paymentInfo['Pay_PaymentType']); ?></strong></span>
                                    <span class="pay-status-label <?php echo strtolower($paymentInfo['Pay_PaymentStatus']); ?>">
                                        <?php echo htmlspecialchars($paymentInfo['Pay_PaymentStatus']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <span><?php echo (int) $item['OrderItem_Quantity']; ?>x <?php echo htmlspecialchars($item['Menu_Name']); ?></span>
                                        <span>₱<?php echo number_format($item['OrderItem_Total'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <h3>No orders found</h3>
                <p>There are no orders with this status.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($orders as $order): ?>
            <div class="order-card <?php echo strtolower($order['Order_Status']); ?>">
                <div class="order-header">
                    <div>
                        <h3>Order #<?php echo $order['Order_Id']; ?></h3>
                        <span class="status-badge <?php echo strtolower($order['Order_Status']); ?>">
                            <?php echo htmlspecialchars($order['Order_Status']); ?>
                        </span>
                    </div>
                    <div style="text-align:right;">
                        <div class="order-time"><?php echo date('M d, Y - h:i A', strtotime($order['Order_OrderDate'])); ?></div>
                        <?php if (!empty($order['Order_PrepTime'])): ?>
                            <div class="prep-time-display">&nbsp;&#9200; <?php echo (int) $order['Order_PrepTime']; ?> mins</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-customer">
                    <strong><?php echo htmlspecialchars($order['Cust_FName'] . ' ' . $order['Cust_LName']); ?></strong>
                    &nbsp;|&nbsp; Items: <?php echo (int) $order['Order_Quantity']; ?>
                    &nbsp;|&nbsp; Total: ₱<?php echo number_format($order['Order_TotalAmount'], 2); ?>
                </div>
                <?php if (!empty($order['Add_Street'])): ?>
                    <div class="order-customer" style="font-size:13px;color:#666;">
                        &#128205; <?php echo htmlspecialchars($order['Add_Street']); ?>, Brgy. <?php echo htmlspecialchars($order['Add_Barangay']); ?>,
                        <?php echo htmlspecialchars($order['Add_Municipality']); ?>, <?php echo htmlspecialchars($order['Add_City']); ?> <?php echo htmlspecialchars($order['Add_PostalCode']); ?>
                    </div>
                <?php endif; ?>

                <?php
                $paymentInfo = mcd_get_payment_info($conn, $order['Order_Id']);
                ?>
                <?php if ($paymentInfo): ?>
                    <div class="order-payment">
                        <span>Payment: <strong><?php echo htmlspecialchars($paymentInfo['Pay_PaymentType']); ?></strong></span>
                        <span class="pay-status-label <?php echo strtolower($paymentInfo['Pay_PaymentStatus']); ?>">
                            <?php echo htmlspecialchars($paymentInfo['Pay_PaymentStatus']); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="order-items">
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="order-item">
                            <span><?php echo (int) $item['OrderItem_Quantity']; ?>x <?php echo htmlspecialchars($item['Menu_Name']); ?></span>
                            <span>₱<?php echo number_format($item['OrderItem_Total'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-actions">
                    <?php if ($order['Order_Status'] === 'Pending'): ?>
                        <form method="POST" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <input type="hidden" name="order_id" value="<?php echo $order['Order_Id']; ?>">
                            <span class="prep-time-label">Prep time:</span>
                            <input type="number" name="prep_time" class="prep-time-input" value="15" min="1" max="180">
                            <span class="prep-time-label">mins</span>
                            <button type="submit" name="accept_order" class="accept-btn">&#10003; Accept Order</button>
                        </form>
                    <?php elseif ($order['Order_Status'] === 'Preparing'): ?>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['Order_Id']; ?>">
                            <input type="hidden" name="new_status" value="Ready">
                            <button type="submit" name="update_status" class="accept-btn" style="background:#007bff;">Mark as Ready</button>
                        </form>
                    <?php elseif ($order['Order_Status'] === 'Ready'): ?>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['Order_Id']; ?>">
                            <input type="hidden" name="new_status" value="Completed">
                            <button type="submit" name="update_status" class="accept-btn" style="background:#6c757d;">Mark Completed</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleCustomer(idx) {
    var el = document.getElementById('customer-' + idx);
    var header = el.previousElementSibling;
    if (el.classList.contains('open')) {
        el.classList.remove('open');
        header.classList.remove('open');
    } else {
        el.classList.add('open');
        header.classList.add('open');
    }
}
</script>
