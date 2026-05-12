<?php
session_start();

if (!isset($_SESSION['Staff_Id'])) {
    header('Location: staff_login.php');
    exit;
}

include('config/db.php');
require_once('includes/cart.php');

$staffRole = $_SESSION['Staff_Role'] ?? 'Staff';

// If kitchen staff, default to orders page
if ($staffRole !== 'Admin' && !isset($_GET['page'])) {
    $_GET['page'] = 'orders';
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
        <nav class="admin-nav">
            <?php if ($staffRole === 'Admin'): ?>
                <a href="admin_dashboard.php?page=overview">Overview</a>
                <a href="admin_dashboard.php?page=products">Manage Products</a>
                <a href="admin_dashboard.php?page=orders">Orders</a>
                <a href="admin_dashboard.php?page=staff">Manage Staff</a>
            <?php else: ?>
                <a href="admin_dashboard.php?page=orders">Orders</a>
            <?php endif; ?>
            <a href="logout.php?from=admin">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <?php if(isset($msg)): ?>
            <div class="alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <?php 
            $page = $_GET['page'] ?? 'overview';
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
                $recentOrders = mysqli_query($conn, "SELECT o.*, c.Cust_FName, c.Cust_LName FROM mcorder o INNER JOIN customer c ON c.Cust_Id = o.Order_Cust_Id ORDER BY o.Order_OrderDate DESC LIMIT 5");
                ?>
                <div class="overview-header">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['Staff_FName']); ?></strong>! You are logged in as <span class="role-badge"><?php echo htmlspecialchars($staffRole); ?></span></p>
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
                    <div class="stat-card" style="border-left: 4px solid #ffc107;">
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $pendingOrders; ?></span>
                            <span class="stat-label">Pending Orders</span>
                        </div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #007bff;">
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $preparingOrders; ?></span>
                            <span class="stat-label">Preparing Orders</span>
                        </div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #28a745;">
                        <div class="stat-info">
                            <span class="stat-number">₱<?php echo number_format($revenue, 2); ?></span>
                            <span class="stat-label">Completed Revenue</span>
                        </div>
                    </div>
                </div>

                <div class="overview-section">
                    <h2>Recent Orders</h2>
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
                        <tbody>
                            <?php while ($ro = mysqli_fetch_assoc($recentOrders)): ?>
                                <tr>
                                    <td class="text-bold">#<?php echo $ro['Order_Id']; ?></td>
                                    <td><?php echo htmlspecialchars($ro['Cust_FName'] . ' ' . $ro['Cust_LName']); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($ro['Order_Status']); ?></span></td>
                                    <td>₱<?php echo number_format($ro['Order_TotalAmount'], 2); ?></td>
                                    <td><?php echo date('M d, Y - h:i A', strtotime($ro['Order_OrderDate'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php
            }
        ?>
    </main>
</div>
</body>
</html>