<?php
session_start();

if (!isset($_SESSION['Staff_Id'])) {
    header('Location: staff_login.php');
    exit;
}

include('config/db.php');
require_once('includes/cart.php');

$staffRole = $_SESSION['Staff_Role'] ?? 'Staff';
$isSystemAdmin = isset($_SESSION['Staff_Role']) && $_SESSION['Staff_Role'] === 'System Admin';

// Fetch branch name for manager/staff
$staffBranchName = '';
if (isset($_SESSION['Staff_Brnch_Id'])) {
    $db = $firestore->database();
    $branchDoc = $db->collection('branches')->document($_SESSION['Staff_Brnch_Id'])->snapshot();
    $staffBranchName = $branchDoc->exists() ? ($branchDoc->data()['Brnch_Name'] ?? '') : '';
}

// Determine current page
$page = $_GET['page'] ?? 'overview';

// Set default page based on role
if (!isset($_GET['page'])) {
    if ($isSystemAdmin) {
        $page = 'overview';
    } elseif ($staffRole === 'Manager') {
        $page = 'overview';
    } elseif (in_array($staffRole, ['Kitchen Staff', 'Rider'])) {
        $page = 'orders';
    }
}

// Processing Logic for Products
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mcdomenuitem'])) {
    $name = $_POST['Menu_Name'];
    $desc = $_POST['Menu_Description'];
    $price = $_POST['Menu_Price'];
    $category = $_POST['Menu_Category'];
    
    $target_dir = "uploads"; 
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $image_name = time() . "_" . basename($_FILES["Menu_Image"]["name"]);
    $target_file = $target_dir . "/" . $image_name;
    
    if (move_uploaded_file($_FILES["Menu_Image"]["tmp_name"], $target_file)) {
        $db = $firestore->database();
        $db->collection('menuItems')->add([
            'Menu_Name' => $name,
            'Menu_Description' => $desc,
            'Menu_Price' => (float) $price,
            'Menu_Category' => $category,
            'Menu_ImageURL' => $image_name,
            'Menu_Available' => true,
        ]);
        $msg = "Item Added Successfully!";
    }
}

// Logic for UPDATING an existing item
if (isset($_POST['update_product'])) {
    $id = $_POST['update_id'];
    $name = $_POST['Menu_Name'];
    $desc = $_POST['Menu_Description'];
    $price = $_POST['Menu_Price'];
    $category = $_POST['Menu_Category'];

    $db = $firestore->database();
    $docRef = $db->collection('menuItems')->document($id);

    // If new image is uploaded
    if (!empty($_FILES["Menu_Image"]["name"])) {
        $img = time() . "_" . $_FILES["Menu_Image"]["name"];
        move_uploaded_file($_FILES["Menu_Image"]["tmp_name"], "uploads/" . $img);
        $docRef->set([
            'Menu_Name' => $name,
            'Menu_Description' => $desc,
            'Menu_Price' => (float) $price,
            'Menu_Category' => $category,
            'Menu_ImageURL' => $img,
        ], ['merge' => true]);
    } else {
        $docRef->set([
            'Menu_Name' => $name,
            'Menu_Description' => $desc,
            'Menu_Price' => (float) $price,
            'Menu_Category' => $category,
        ], ['merge' => true]);
    }
    
    $msg = "Item updated successfully!";
}

// Delete logic
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $db = $firestore->database();
    $db->collection('menuItems')->document($id)->delete();
    header("Location: admin_dashboard.php?page=products&status=deleted");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>McDelivery Admin</title>
    <link rel="stylesheet" href="css/admin/admin_style.css">                                                                                           
</head>         
<body>                  
<div class="admin-container">       
    <aside class="admin-sidebar">                                                                      
        <div class="admin-logo">McDelivery Admin</div>
        <?php if ($staffBranchName): ?>
            <div style="padding: 8px 20px; font-size: 12px; color: #ffbc0d; border-bottom: 1px solid rgba(255,255,255,0.08);">
                &#127963; <?php echo htmlspecialchars($staffBranchName); ?>
            </div>
        <?php endif; ?>
        <nav class="admin-nav" id="adminNav">
            <?php if ($isSystemAdmin): ?>
                <a href="admin_dashboard.php?page=overview"<?php if ($page === 'overview') echo ' class="active"'; ?>>Dashboard</a>
                <a href="admin_dashboard.php?page=branches"<?php if ($page === 'branches') echo ' class="active"'; ?>>Manage Branches</a>
                <a href="admin_dashboard.php?page=managers"<?php if ($page === 'managers') echo ' class="active"'; ?>>Manage Managers</a>
                <a href="admin_dashboard.php?page=staff"<?php if ($page === 'staff') echo ' class="active"'; ?>>Manage Staff</a>
                <a href="admin_dashboard.php?page=products"<?php if ($page === 'products') echo ' class="active"'; ?>>Manage Products</a>
                <a href="admin_dashboard.php?page=orders"<?php if ($page === 'orders') echo ' class="active"'; ?>>Orders</a>
            <?php elseif ($staffRole === 'Manager'): ?>
                <a href="admin_dashboard.php?page=overview"<?php if ($page === 'overview') echo ' class="active"'; ?>><?php echo htmlspecialchars($staffBranchName ?: 'Dashboard'); ?></a>
                <a href="admin_dashboard.php?page=staff"<?php if ($page === 'staff') echo ' class="active"'; ?>>Manage Staff</a>
                <a href="admin_dashboard.php?page=orders"<?php if ($page === 'orders') echo ' class="active"'; ?>>Orders</a>
            <?php else: ?>
                <a href="admin_dashboard.php?page=orders"<?php if ($page === 'orders') echo ' class="active"'; ?>>Orders</a>
            <?php endif; ?>
            <a href="logout.php?from=admin">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <?php if(isset($msg)): ?>
            <div class="alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <?php
            $branchId = isset($_SESSION['Staff_Brnch_Id']) ? $_SESSION['Staff_Brnch_Id'] : null;

            if ($page == 'products') {
                $db = $firestore->database();
                $menuSnapshot = $db->collection('menuItems')->orderBy('Menu_Category')->documents();
                $all_items = [];
                foreach ($menuSnapshot as $doc) {
                    if ($doc->exists()) {
                        $data = $doc->data();
                        $data['Menu_MenuItemId'] = $doc->id();
                        $all_items[] = $data;
                    }
                }
                // Wrap as iterable object for mysqli_fetch_assoc compatibility in admin_add_products.php
                $all_items_iterable = new ArrayIterator($all_items);
                include 'admin_add_products.php';
            } elseif ($page == 'branches') {
                include 'admin_manage_users.php';
            } elseif ($page == 'managers') {
                include 'admin_manage_managers.php';
            } elseif ($page == 'staff') {
                include 'admin_manage_users.php';
            } elseif ($page == 'orders') {
                include 'admin_kitchen_orders.php';
            } elseif ($page == 'overview' && $staffRole === 'Manager' && $branchId) {
                $db = $firestore->database();
                
                // Branch info
                $branchDoc = $db->collection('branches')->document($branchId)->snapshot();
                $branchInfo = $branchDoc->exists() ? $branchDoc->data() : [];
                $branchInfo['Brnch_Id'] = $branchDoc->id();
                
                // Orders for this branch
                $branchOrders = $db->collection('orders')
                    ->where('Order_Brnch_Id', '=', $branchId)
                    ->documents();
                
                $totalBranchOrders = 0;
                $pendingBranchOrders = 0;
                $preparingBranchOrders = 0;
                $readyBranchOrders = 0;
                $completedBranchOrders = 0;
                $branchRevenue = 0;
                
                foreach ($branchOrders as $ord) {
                    if (!$ord->exists()) continue;
                    $o = $ord->data();
                    $totalBranchOrders++;
                    
                    $status = $o['Order_Status'] ?? '';
                    switch ($status) {
                        case 'Pending': $pendingBranchOrders++; break;
                        case 'Preparing': $preparingBranchOrders++; break;
                        case 'Ready': $readyBranchOrders++; break;
                        case 'Completed': 
                            $completedBranchOrders++; 
                            $branchRevenue += (float) ($o['Order_TotalAmount'] ?? 0);
                            break;
                    }
                }
                
                // Staff count
                $staffDocs = $db->collection('staff')
                    ->where('Staff_Brnch_Id', '=', $branchId)
                    ->documents();
                $branchStaffCount = 0;
                foreach ($staffDocs as $s) {
                    if ($s->exists()) $branchStaffCount++;
                }
                ?>
                <div class="overview-header">
                    <div>
                        <h1>&#127963; <?php echo htmlspecialchars($branchInfo['Brnch_Name'] ?? "Branch #$branchId"); ?></h1>
                        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['Staff_FName']); ?></strong></p>
                        <?php
                        $bAddr = [];
                        if (!empty($branchInfo['Brnch_Street'])) $bAddr[] = $branchInfo['Brnch_Street'];
                        if (!empty($branchInfo['Brnch_Barangay'])) $bAddr[] = 'Brgy. ' . $branchInfo['Brnch_Barangay'];
                        if (!empty($branchInfo['Brnch_City'])) $bAddr[] = $branchInfo['Brnch_City'];
                        if (!empty($branchInfo['Brnch_Municipality'])) $bAddr[] = $branchInfo['Brnch_Municipality'];
                        if ($bAddr) echo '<p style="font-size:13px;color:#888;margin-top:4px;">' . htmlspecialchars(implode(', ', $bAddr)) . '</p>';
                        ?>
                    </div>
                    <span class="role-badge"><?php echo htmlspecialchars($staffRole); ?></span>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fff3cd;">&#128203;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $totalBranchOrders; ?></span>
                            <span class="stat-label">Total Orders</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#cce5ff;">&#128100;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $branchStaffCount; ?></span>
                            <span class="stat-label">Staff</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#f8d7da;">&#9200;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $pendingBranchOrders; ?></span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#007bff;color:white;">&#128260;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $preparingBranchOrders; ?></span>
                            <span class="stat-label">Preparing</span>
                        </div>
                    </div>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="stat-card order-stat">
                        <div class="stat-dot" style="background:#28a745;"></div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $readyBranchOrders; ?></span>
                            <span class="stat-label">Ready</span>
                        </div>
                    </div>
                    <div class="stat-card order-stat">
                        <div class="stat-dot" style="background:#6c757d;"></div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $completedBranchOrders; ?></span>
                            <span class="stat-label">Completed</span>
                        </div>
                    </div>
                    <div class="stat-card order-stat">
                        <div class="stat-dot" style="background:#28a745;"></div>
                        <div class="stat-info">
                            <span class="stat-number">₱<?php echo number_format($branchRevenue, 2); ?></span>
                            <span class="stat-label">Revenue</span>
                        </div>
                    </div>
                </div>
                <?php
            } elseif ($page == 'overview') {
                $db = $firestore->database();
                
                // Global counts
                $menuCount = 0;
                foreach ($db->collection('menuItems')->documents() as $d) { if ($d->exists()) $menuCount++; }
                
                $orderCount = 0;
                $pendingOrders = 0;
                $preparingOrders = 0;
                $revenue = 0;
                
                $allOrders = $db->collection('orders')->documents();
                $ordersData = [];
                foreach ($allOrders as $ordDoc) {
                    if (!$ordDoc->exists()) continue;
                    $o = $ordDoc->data();
                    $o['Order_Id'] = $ordDoc->id();
                    $orderCount++;
                    
                    $status = $o['Order_Status'] ?? '';
                    if ($status === 'Pending') $pendingOrders++;
                    if ($status === 'Preparing') $preparingOrders++;
                    if ($status === 'Completed') {
                        $revenue += (float) ($o['Order_TotalAmount'] ?? 0);
                    }
                    
                    $ordersData[] = $o;
                }
                
                // Sort by date descending
                usort($ordersData, function($a, $b) {
                    $aTime = isset($a['Order_OrderDate']) ? (is_string($a['Order_OrderDate']) ? strtotime($a['Order_OrderDate']) : $a['Order_OrderDate']->get()->format('U')) : 0;
                    $bTime = isset($b['Order_OrderDate']) ? (is_string($b['Order_OrderDate']) ? strtotime($b['Order_OrderDate']) : $b['Order_OrderDate']->get()->format('U')) : 0;
                    return $bTime - $aTime;
                });
                
                $ordersData = array_slice($ordersData, 0, 20);
                
                $customerCount = 0;
                foreach ($db->collection('customers')->documents() as $d) { if ($d->exists()) $customerCount++; }
                
                $staffCount = 0;
                foreach ($db->collection('staff')->documents() as $d) { if ($d->exists()) $staffCount++; }
                
                // Build items array for each order
                $itemsByOrder = [];
                foreach ($ordersData as $ro) {
                    $roItems = $ro['items'] ?? [];
                    $itemsByOrder[$ro['Order_Id']] = [];
                    foreach ($roItems as $item) {
                        $itemsByOrder[$ro['Order_Id']][] = $item;
                    }
                }
                ?>
                <div class="overview-header">
                    <div>
                        <h1>Dashboard Overview</h1>
                        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['Staff_FName']); ?></strong></p>
                    </div>
                    <span class="role-badge"><?php echo htmlspecialchars($staffRole); ?></span>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fff3cd;">&#128230;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $menuCount; ?></span>
                            <span class="stat-label">Total Products</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#cce5ff;">&#128203;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $orderCount; ?></span>
                            <span class="stat-label">Total Orders</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#d4edda;">&#128101;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $customerCount; ?></span>
                            <span class="stat-label">Total Customers</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#f8d7da;">&#128100;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $staffCount; ?></span>
                            <span class="stat-label">Total Staff</span>
                        </div>
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="stat-card order-stat">
                        <div class="stat-dot" style="background:#ffc107;"></div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $pendingOrders; ?></span>
                            <span class="stat-label">Pending Orders</span>
                        </div>
                    </div>
                    <div class="stat-card order-stat">
                        <div class="stat-dot" style="background:#007bff;"></div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $preparingOrders; ?></span>
                            <span class="stat-label">Preparing Orders</span>
                        </div>
                    </div>
                    <div class="stat-card order-stat">
                        <div class="stat-dot" style="background:#28a745;"></div>
                        <div class="stat-info">
                            <span class="stat-number">₱<?php echo number_format($revenue, 2); ?></span>
                            <span class="stat-label">Completed Revenue</span>
                        </div>
                    </div>
                </div>

                <div class="overview-section">
                    <div class="ov-header">
                        <h2>Recent Orders</h2>
                        <span class="item-count"><?php echo count($ordersData); ?> orders</span>
                    </div>
                    <div class="ov-search">
                        <span class="search-icon">&#128269;</span>
                        <input type="text" id="ovSearch" onkeyup="filterOverview()" placeholder="Search by order # or customer name...">
                    </div>
                    <div class="ov-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="ovTableBody">
                            <?php foreach ($ordersData as $ro): ?>
                                <tr class="clickable-row" onclick="openOrderModal(<?php echo htmlspecialchars(json_encode($ro)); ?>, <?php echo htmlspecialchars(json_encode($itemsByOrder[$ro['Order_Id']] ?? [])); ?>)">
                                    <td class="text-bold">#<?php echo $ro['Order_Id']; ?></td>
                                    <td><?php echo htmlspecialchars(($ro['Cust_FName'] ?? '') . ' ' . ($ro['Cust_LName'] ?? '')); ?></td>
                                    <td><span class="badge status-<?php echo strtolower($ro['Order_Status']); ?>"><?php echo htmlspecialchars($ro['Order_Status']); ?></span></td>
                                    <td>₱<?php echo number_format($ro['Order_TotalAmount'], 2); ?></td>
                                    <td><?php echo date('M d, Y - h:i A', strtotime($ro['Order_OrderDate'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Order Detail Modal -->
                <div id="orderModal" class="modal-overlay">
                    <div class="modal-content modal-lg">
                        <span class="close-btn" onclick="closeOrderModal()">&times;</span>
                        <div id="orderModalBody"></div>
                    </div>
                </div>
                <?php
            }
        ?>
    </main>
</div>

<script>
function filterOverview() {
    var input = document.getElementById('ovSearch');
    var filter = input.value.toLowerCase();
    var tbody = document.getElementById('ovTableBody');
    var rows = tbody.getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var text = rows[i].textContent || rows[i].innerText;
        rows[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

function openOrderModal(order, items) {
    var statusClass = 'status-' + order.Order_Status.toLowerCase();
    var orderDate = new Date(order.Order_OrderDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });

    var itemsHtml = '';
    if (items && items.length > 0) {
        itemsHtml = '<table class="detail-table"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>';
        for (var i = 0; i < items.length; i++) {
            itemsHtml += '<tr>' +
                '<td>' + items[i].Menu_Name + '</td>' +
                '<td>' + items[i].OrderItem_Quantity + '</td>' +
                '<td>₱' + parseFloat(items[i].OrderItem_Price).toFixed(2) + '</td>' +
                '<td>₱' + parseFloat(items[i].OrderItem_Total).toFixed(2) + '</td>' +
                '</tr>';
        }
        itemsHtml += '</tbody></table>';
    } else {
        itemsHtml = '<p style="color:#999;text-align:center;padding:20px;">No item details available.</p>';
    }

    var html =
        '<div class="modal-two-col">' +
            '<div class="modal-customer">' +
                '<h3>Customer Information</h3>' +
                '<div class="info-grid">' +
                    '<div class="info-item"><span class="info-label">Name</span><span class="info-value">' + (order.Cust_FName || '') + ' ' + (order.Cust_LName || '') + '</span></div>' +
                '</div>' +
            '</div>' +
            '<div class="modal-order">' +
                '<h3>Order #' + order.Order_Id + '</h3>' +
                '<div class="order-meta">' +
                    '<span class="badge ' + statusClass + '">' + order.Order_Status + '</span>' +
                    '<span style="color:#888;font-size:13px;">' + orderDate + '</span>' +
                '</div>' +
                '<div style="margin:12px 0 8px;font-size:15px;font-weight:bold;">Items</div>' +
                itemsHtml +
                '<div class="order-sub-row"><span>Subtotal</span><span>₱' + (parseFloat(order.Order_TotalAmount) - parseFloat(order.Order_DeliveryFee || 0)).toFixed(2) + '</span></div>' +
                '<div class="order-del-row"><span>Delivery Fee</span><span>₱' + parseFloat(order.Order_DeliveryFee || 0).toFixed(2) + '</span></div>' +
                '<div class="order-total-row"><span>Total Amount</span><span>₱' + parseFloat(order.Order_TotalAmount).toFixed(2) + '</span></div>' +
            '</div>' +
        '</div>';

    document.getElementById('orderModalBody').innerHTML = html;
    document.getElementById('orderModal').style.display = 'block';
}

function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('orderModal')) {
        closeOrderModal();
    }
};
</script>

</body>
</html>
