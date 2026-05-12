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
            <form action="menu.php" method="GET" style="display:flex;width:100%;">
                <input type="text" name="search" placeholder="Search for your McDonald's favorite" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="flex:1;">
                
            </form>
        </div>

        <nav>
            <a href="index.php">Home</a>
            <a href="menu.php">Menu</a>
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
            <?php endif; ?>

            <a href="javascript:void(0)" onclick="toggleBag()">
            <img src="images/bag.jpg" alt="My Bag" style="width: 30px;">
            <span class="bag-count"><?php echo $bagCount; ?></span>
        </a>
        </nav>
    </div>
</header>

<div id="loginModal" class="modal-overlay" style="display: <?php echo (isset($_SESSION['show_login_modal']) && $_SESSION['show_login_modal']) ? 'flex' : 'none'; ?>;">
    <div class="modal-card">
        <span class="close-btn" onclick="closeLogin()">&times;</span>
        
        <div class="modal-header">
            <img src="images/mcdologo.png" alt="Logo" class="modal-logo">
            <h2>Welcome back!</h2>
            <p>Sign in to your account.</p>
        </div>
<?php 
    if (isset($_SESSION['login_error'])): 
    ?>
        <div class="error-banner">
            <span class="icon">&#9888;</span>
            <?php 
                echo $_SESSION['login_error']; 
                unset($_SESSION['login_error']);
            ?>
        </div>
    <?php endif; 
    if (isset($_SESSION['reg_success'])): 
        unset($_SESSION['reg_success']);
    ?>
        <div class="error-banner" style="background:#d4edda;color:#155724;border-left-color:#28a745;">
            <span class="icon">&#10003;</span>
            Account created successfully! Please log in.
        </div>
    <?php endif;
    unset($_SESSION['show_login_modal']);
    ?>
        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Email" value="<?php echo isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : ''; ?>" required>
            <input type="password" name="password" placeholder="Password" required>
            <a href="#" class="forgot-pass">Forgot your password?</a>
            <button type="submit" class="btn-login-submit">Log In</button>
        </form>

        <div class="modal-divider"><span></span></div>

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
<?php unset($_SESSION['login_email']); ?>
