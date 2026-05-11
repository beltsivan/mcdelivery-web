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
                echo "<h1>Dashboard Overview</h1>";
                echo "<p>Welcome, " . htmlspecialchars($_SESSION['Staff_FName']) . "! You are logged in as <strong>" . htmlspecialchars($staffRole) . "</strong>.</p>";
            }
        ?>
    </main>
</div>
</body>
</html>