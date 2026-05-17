<?php
session_start();

if (!isset($_SESSION['Staff_Id'])) {
    header('Location: staff_login.php');
    exit;
}

include('config/db.php');
require_once('includes/cart.php');

$staffRole = $_SESSION['Staff_Role'] ?? 'Staff';

// Determine current page
$page = $_GET['page'] ?? 'overview';

// If kitchen staff, default to orders page
if ($staffRole !== 'Admin' && !isset($_GET['page'])) {
    $page = 'orders';
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
        $sql = "INSERT INTO mcdomenuitem (Menu_Name, Menu_Description, Menu_Price, Menu_Category, Menu_ImageURL, Menu_Available) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdss", $name, $desc, $price, $category, $image_name);
        $stmt->execute();
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

    // If new image is uploaded
    if (!empty($_FILES["Menu_Image"]["name"])) {
        $img = time() . "_" . $_FILES["Menu_Image"]["name"];
        move_uploaded_file($_FILES["Menu_Image"]["tmp_name"], "uploads/" . $img);
        
        $sql = "UPDATE mcdomenuitem SET Menu_Name=?, Menu_Description=?, Menu_Price=?, Menu_Category=?, Menu_ImageURL=? WHERE Menu_MenuItemId=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $name, $desc, $price, $category, $img, $id);
    } else {
        // Update without changing the image
        $sql = "UPDATE mcdomenuitem SET Menu_Name=?, Menu_Description=?, Menu_Price=?, Menu_Category=? WHERE Menu_MenuItemId=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsi", $name, $desc, $price, $category, $id);
    }
    
    if ($stmt->execute()) { $msg = "Item updated successfully!"; }
}



// 2. The Delete Logic
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    
    $sql = "DELETE FROM mcdomenuitem WHERE Menu_MenuItemId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redirect back to the same page but without the delete_id in the URL
        header("Location: admin_dashboard.php?page=products&status=deleted");
        exit();
    }
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
        <nav class="admin-nav" id="adminNav">
            <?php if ($staffRole === 'Admin'): ?>
                <a href="admin_dashboard.php?page=overview"<?php if ($page === 'overview') echo ' class="active"'; ?>>Overview</a>
                <a href="admin_dashboard.php?page=products"<?php if ($page === 'products') echo ' class="active"'; ?>>Manage Products</a>
                <a href="admin_dashboard.php?page=orders"<?php if ($page === 'orders') echo ' class="active"'; ?>>Orders</a>
                <a href="admin_dashboard.php?page=staff"<?php if ($page === 'staff') echo ' class="active"'; ?>>Manage Staff</a>
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
            if ($page == 'products') {
                $all_items = mysqli_query($conn, "SELECT * FROM mcdomenuitem ORDER BY Menu_Category ASC");
                include 'admin_add_products.php';
            } elseif ($page == 'staff') {
                include 'admin_manage_users.php';
            } elseif ($page == 'orders') {
                include 'admin_kitchen_orders.php';
            } else {
                $totalProducts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM mcdomenuitem"))['c'];
                $totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM mcorder"))['c'];
                $totalCustomers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM customer"))['c'];
                $totalStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM staff"))['c'];
                $pendingOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM mcorder WHERE Order_Status = 'Pending'"))['c'];
                $preparingOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM mcorder WHERE Order_Status = 'Preparing'"))['c'];
                $revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(Order_TotalAmount), 0) AS t FROM mcorder WHERE Order_Status = 'Completed'"))['t'];
                $recentOrdersResult = mysqli_query($conn, "SELECT o.*, c.Cust_FName, c.Cust_LName, c.Cust_Email, c.Cust_Phone, c.Cust_CreatedAt FROM mcorder o INNER JOIN customer c ON c.Cust_Id = o.Order_Cust_Id ORDER BY o.Order_OrderDate DESC LIMIT 20");

                $ordersData = [];
                $orderIds = [];
                while ($ro = mysqli_fetch_assoc($recentOrdersResult)) {
                    $ordersData[] = $ro;
                    $orderIds[] = $ro['Order_Id'];
                }

                $itemsByOrder = [];
                if (!empty($orderIds)) {
                    $ids = implode(',', array_map('intval', $orderIds));
                    $itemsResult = mysqli_query($conn, "SELECT oi.*, m.Menu_Name FROM orderitem oi INNER JOIN mcdomenuitem m ON m.Menu_MenuItemId = oi.OrderItem_MenuItemId WHERE oi.OrderItem_Order_Id IN ($ids)");
                    while ($item = mysqli_fetch_assoc($itemsResult)) {
                        $itemsByOrder[$item['OrderItem_Order_Id']][] = $item;
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
                            <span class="stat-number"><?php echo $totalProducts; ?></span>
                            <span class="stat-label">Total Products</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#cce5ff;">&#128203;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $totalOrders; ?></span>
                            <span class="stat-label">Total Orders</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#d4edda;">&#128101;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $totalCustomers; ?></span>
                            <span class="stat-label">Total Customers</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#f8d7da;">&#128100;</div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $totalStaff; ?></span>
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
                                    <td><?php echo htmlspecialchars($ro['Cust_FName'] . ' ' . $ro['Cust_LName']); ?></td>
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
    var customerSince = order.Cust_CreatedAt ? new Date(order.Cust_CreatedAt).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
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
                    '<div class="info-item"><span class="info-label">Name</span><span class="info-value">' + order.Cust_FName + ' ' + order.Cust_LName + '</span></div>' +
                    '<div class="info-item"><span class="info-label">Email</span><span class="info-value">' + (order.Cust_Email || 'N/A') + '</span></div>' +
                    '<div class="info-item"><span class="info-label">Phone</span><span class="info-value">' + (order.Cust_Phone || 'N/A') + '</span></div>' +
                    '<div class="info-item"><span class="info-label">Customer Since</span><span class="info-value">' + customerSince + '</span></div>' +
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