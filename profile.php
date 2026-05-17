<?php
include('includes/header.php'); 
include('config/db.php'); 

// Redirect guest users away from this page
if (!isset($_SESSION['Cust_Id'])) {
    header("Location: index.php");
    exit();
}

$cust_id = $_SESSION['Cust_Id'];

// Fetch current user data
$query = "SELECT * FROM Customer WHERE Cust_Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/style.css">
    <style>
        
    </style>
</head>
<body style="background-color: #fcfcfc;">

    <div class="profile-wrapper">
        <aside class="profile-sidebar">
            <h3>My Account</h3>
            <ul class="sidebar-menu">
                <li><a href="profile.php" class="active">My Profile</a></li>
                <li><a href="address.php">My Addresses</a></li>
                <li><a href="contact.php">My Contact Numbers</a></li>
                <li><a href="#">Coupons</a></li>
            </ul>
        </aside>

        <main class="profile-main">
            <div class="profile-card">
                <h2>My Profile</h2>
                
                <form action="update_profile.php" method="POST">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="fname" value="<?php echo $user['Cust_FName']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lname" value="<?php echo $user['Cust_LName']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo $user['Cust_Email']; ?>" readonly style="color: #aaa;">
                    </div>

                    <button type="submit" class="btn-save">Save</button>
                    <a href="#" class="change-pass">Change Password</a>
                </form>
            </div>
        </main>
    </div>

</body>
</html>
<?php include('includes/footer.php'); ?>