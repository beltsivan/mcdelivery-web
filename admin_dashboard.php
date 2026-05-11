<?php
include('config/db.php'); 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mcdomenuitem'])) {
    $name = $_POST['Menu_Name'];
    $desc = $_POST['Menu_Description'];
    $price = $_POST['Menu_Price'];
    $category = $_POST['Menu_Category'];
    
    // Image Upload Logic
    $target_dir = "uploads/"; 
    $image_name = time() . "_" . basename($_FILES["Menu_Image"]["name"]); // Add timestamp to avoid duplicate names
    $target_file = $target_dir . $image_name;
    
    // Move the file from temporary storage to your 'uploads' folder
    if (move_uploaded_file($_FILES["Menu_Image"]["tmp_name"], $target_file)) {
        // SQL: Use $image_name for the Menu_ImageURL column
        $sql = "INSERT INTO Menu (Menu_Name, Menu_Description, Menu_Price, Menu_Category, Menu_ImageURL) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdss", $name, $desc, $price, $category, $image_name);

        if ($stmt->execute()) {
            echo "Success! Item added to menu.";
        } else {
            echo "Database error: " . $conn->error;
        }
    } else {
        echo "Error: Could not upload image.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Control Center</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

<div class="admin-container">
    <aside class="admin-sidebar">
        <div class="logo">McDelivery Admin</div>
        <nav>
            <a href="?page=overview">Overview</a>
            <a href="?page=orders">Orders Queue</a>
            <a href="?page=products">Manage Products</a>
            <a href="?page=staff">Manage Staff/Riders</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Welcome, Admin</h1>
            <a href="logout.php">Logout</a>
        </header>

        <section class="content-area">
            <?php 
                $page = $_GET['page'] ?? 'overview';
                
                if ($page == 'products') {
                    include 'admin_add_products.php';
                } elseif ($page == 'staff') {
                    include 'admin_manage_users.php';
                } elseif ($page == 'orders') {
                    include 'admin_orders_list.php';
                } else {
                    echo "<h2>Dashboard Summary</h2>";
                    // Display quick stats here
                }
            ?>
        </section>
    </main>
</div>

</body>
</html>