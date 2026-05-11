<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/cart.php');

$bagCount = 0;

if (isset($_SESSION['Cust_Id'])) {
    $bagCount = mcd_get_customer_bag_count($conn, (int) $_SESSION['Cust_Id']);
} else {
    $bagCount = mcd_get_guest_bag_count();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>McDelivery Imitation</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/banner.css">
    <link rel="stylesheet" href="css/menu.css?v=<?php echo filemtime(__DIR__ . '/../css/menu.css'); ?>">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="css/account.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/address.css">
    <link href="https://fonts.googleapis.com/css2?family=Speedee:wght@400;700&display=swap" rel="stylesheet">
    <script src="js/auth-modal.js" defer></script>
    <script src="js/register.js" defer></script>
    <script src="js/login.js" defer></script>
</head>
<body>
<header>
    <div class="nav-container">
        <a href="index.php">
            <div class="logo">
                <img src="images/mcLogo.jpg" alt="McDonalds">
                <span>McDelivery</span>
            </div>
        </a>

        <div class="search-bar">
            <input type="text" placeholder="Search for your McDonald's favorite">
        </div>

        <nav>
            <a href="index.php">Home</a>
            <a href="menu.php">Menu</a>
            <a href="#">Send to Many</a>
            <a href="orders.php">Orders</a>

            <?php if (isset($_SESSION['Cust_Id'])): ?>
                <div class="account-dropdown">
                    <button class="dropbtn">My Account</button>
                    <div class="dropdown-content">
                        <a href="profile.php">My Profile</a>
                        <a href="address.php">My Addresses</a>
                        <a href="contact.php">My Contact Number</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="register.php" class="login-btn">Sign Up</a>
                <span style="font-weight: bold;">|</span>
                <a href="javascript:void(0)" onclick="showLogin()" class="login-btn">Log In</a>
                <span style="color:#bbb; font-weight:bold;">|</span>
                <a href="staff_login.php" style="color:#555; font-weight:bold; font-size:13px;">Staff</a>
            <?php endif; ?>

            <a href="javascript:void(0)" onclick="toggleBag()">
            <img src="images/bag.jpg" alt="My Bag" style="width: 30px;">
            <span class="bag-count"><?php echo $bagCount; ?></span>
        </a>
        </nav>
    </div>
</header>

<div id="loginModal" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <span class="close-btn" onclick="closeLogin()">&times;</span>
        
        <div class="modal-header">
            <img src="images/mcLogo.jpg" alt="Logo" class="modal-logo">
            <h2>Welcome back!</h2>
            <p>Sign in to your account.</p>
        </div>
<?php 
    // Ensure session is started
    if (isset($_SESSION['error'])): 
    ?>
        <div class="ui-error-msg">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']); // Remove it so it won't show after refresh
            ?>
        </div>
    <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <a href="#" class="forgot-pass">Forgot your password?</a>
            <button type="submit" class="btn-login-submit">Log In</button>
        </form>

        <div class="modal-divider"><span>OR</span></div>

        <button type="button" class="btn-facebook">Log in with Facebook</button>
        <button type="button" class="btn-guest" onclick="closeLogin()">Continue as guest</button>

        <p class="modal-footer">
            Order fast and easy with a McDelivery account.<br>
            <a href="register.php" class="signup-link">Sign up here.</a>
        </p>
        <p style="margin-top: 10px; font-size: 12px;">
            <a href="staff_login.php" style="color: #888; text-decoration: none;">Staff Login</a>
        </p>
    </div>
</div>
