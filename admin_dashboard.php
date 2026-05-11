<?php
include('config/db.php'); 
session_start();
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
            <a href="admin_dashboard.php?page=overview">Overview</a>
            <a href="admin_dashboard.php?page=products">Manage Products</a>
            <a href="admin_dashboard.php?page=staff">Manage Staff</a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <?php if(isset($msg)): ?>
            <div class="alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <?php 
            $page = $_GET['page'] ?? 'overview';
            if ($page == 'products') {
                // Fetch items for the products panel
                $all_items = mysqli_query($conn, "SELECT * FROM mcdomenuitem ORDER BY Menu_Category ASC");
                include 'admin_add_products.php';
            } elseif ($page == 'staff') {
                include 'admin_manage_users.php';
            } else {
                echo "<h1>Dashboard Overview</h1>";
            }
        ?>
    </main>
</div>
</body>
</html>