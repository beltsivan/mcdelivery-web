<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Cust_Id'])) {
    header('Location: login.php');
    exit;
}

require_once('includes/cart.php');
require_once('config/db.php');
include('includes/header.php');

$custId = (int) $_SESSION['Cust_Id'];

// Check for addresses
$addrResult = mysqli_query($conn, "SELECT * FROM Address WHERE Add_Cust_Id = $custId");
$addresses = mysqli_fetch_all($addrResult, MYSQLI_ASSOC);

if (empty($addresses)) {
    header('Location: address.php?required=1');
    exit;
}

// Process checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['address_id'])) {
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Cash on Delivery';
    $addressId = (int) $_POST['address_id'];

    // Verify address belongs to user
    $valid = false;
    foreach ($addresses as $a) {
        if ($a['Add_Id'] == $addressId) { $valid = true; break; }
    }
    if (!$valid) {
        echo '<div class="error-banner">Invalid address selected.</div>';
    } else {
        $orderId = mcd_checkout($conn, $custId, $addressId, $paymentMethod);

        if ($orderId) {
            $_SESSION['order_success'] = $orderId;
            header('Location: order_success.php?order_id=' . $orderId);
            exit;
        } else {
            echo '<div class="error-banner">Checkout failed. Please try again.</div>';
        }
    }
}
?>

<body>
<div class="reg-container">
    <h1>Checkout</h1>
    <p>Review your order and select a delivery address.</p>

    <div class="reg-card">
        <form method="POST">
            <h3 style="margin-bottom:15px;">Select Delivery Address</h3>
            <?php foreach ($addresses as $addr): ?>
                <label style="display:block;border:2px solid #ddd;border-radius:10px;padding:15px;margin-bottom:10px;cursor:pointer;">
                    <input type="radio" name="address_id" value="<?php echo $addr['Add_Id']; ?>" required>
                    <strong><?php echo htmlspecialchars($addr['Add_Street']); ?></strong>, Brgy. <?php echo htmlspecialchars($addr['Add_Barangay']); ?><br>
                    <?php echo htmlspecialchars($addr['Add_Municipality']); ?>, <?php echo htmlspecialchars($addr['Add_City']); ?> <?php echo htmlspecialchars($addr['Add_PostalCode']); ?>
                </label>
            <?php endforeach; ?>
            <p><a href="address.php?from=checkout" style="color:#DB0007;">+ Add a new address</a></p>

            <hr style="margin:20px 0;">

            <h3 style="margin-bottom:15px;">Payment Method</h3>
            <label style="display:block;padding:8px 0;"><input type="radio" name="payment_method" value="Cash on Delivery" checked> Cash on Delivery</label>
            <label style="display:block;padding:8px 0;"><input type="radio" name="payment_method" value="GCash"> GCash</label>
            <label style="display:block;padding:8px 0;"><input type="radio" name="payment_method" value="Bank Transfer"> Bank Transfer</label>

            <button type="submit" class="btn-create" style="margin-top:20px;" onmouseover="this.style.backgroundColor='#ffbc0d'; this.style.color='black';"
  onmouseout="this.style.backgroundColor=''; this.style.color='';">Place Order</button>
            
        </form>
    </div>
</div>
</body>
<?php include('includes/footer.php'); ?>

