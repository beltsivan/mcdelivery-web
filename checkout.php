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

$custId = $_SESSION['Cust_Id'];
$db = $firestore->database();

// Check if customer selected a branch
$selectedBranch = null;
if (isset($_SESSION['Cust_Brnch_Id'])) {
    $branchDoc = $db->collection('branches')->document((string) $_SESSION['Cust_Brnch_Id'])->snapshot();
    if ($branchDoc->exists()) {
        $selectedBranch = $branchDoc->data();
        $selectedBranch['Brnch_Id'] = $branchDoc->id();
    }
}

// Check for addresses
$addrSnapshot = $db->collection('addresses')
    ->where('Add_Cust_Id', '=', $custId)
    ->documents();

$addresses = [];
foreach ($addrSnapshot as $addrDoc) {
    if ($addrDoc->exists()) {
        $addr = $addrDoc->data();
        $addr['Add_Id'] = $addrDoc->id();
        $addresses[] = $addr;
    }
}

if (empty($addresses)) {
    header('Location: address.php?required=1');
    exit;
}

    // Process checkout
$checkoutError = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['address_id'])) {
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Cash on Delivery';
    $addressId = $_POST['address_id'];

    // Verify address belongs to user
    $valid = false;
    foreach ($addresses as $a) {
        if ($a['Add_Id'] == $addressId) { $valid = true; break; }
    }
    if (!$valid) {
        $checkoutError = 'Invalid address selected.';
    } elseif (!isset($_SESSION['Cust_Brnch_Id'])) {
        $checkoutError = 'Please select a branch before placing an order.';
    } else {
        $branchId = $_SESSION['Cust_Brnch_Id'];
        $orderId = mcd_checkout(null, $custId, $addressId, $paymentMethod, $branchId);

        if ($orderId) {
            $_SESSION['order_success'] = $orderId;
            header('Location: order_success.php?order_id=' . $orderId);
            exit;
        } else {
            $checkoutError = 'Checkout failed. Please try again.';
        }
    }
}
?>

<body>
<div class="reg-container">
    <h1>Checkout</h1>
    <p>Review your order and select a delivery address.</p>
    <?php if ($checkoutError): ?>
        <div class="error-banner"><?php echo htmlspecialchars($checkoutError); ?></div>
    <?php endif; ?>

    <div class="reg-card">
        <form method="POST">
            <?php if ($selectedBranch): ?>
            <div style="background:#fff8e1;border:1px solid #ffbc0d;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
                <strong style="color:#292929;">&#127963; Ordering from: <?php echo htmlspecialchars($selectedBranch['Brnch_Name'] ?: $selectedBranch['Brnch_City'] . ' - ' . $selectedBranch['Brnch_Street']); ?></strong><br>
                <span style="font-size:13px;color:#666;">
                    <?php
                    $bAddr = [];
                    if (!empty($selectedBranch['Brnch_Street'])) $bAddr[] = $selectedBranch['Brnch_Street'];
                    if (!empty($selectedBranch['Brnch_Barangay'])) $bAddr[] = 'Brgy. ' . $selectedBranch['Brnch_Barangay'];
                    if (!empty($selectedBranch['Brnch_City'])) $bAddr[] = $selectedBranch['Brnch_City'];
                    if (!empty($selectedBranch['Brnch_Municipality'])) $bAddr[] = $selectedBranch['Brnch_Municipality'];
                    if (!empty($selectedBranch['Brnch_PostalCode'])) $bAddr[] = $selectedBranch['Brnch_PostalCode'];
                    echo htmlspecialchars(implode(', ', $bAddr));
                    ?>
                </span>
                <a href="branch_select.php" style="display:inline-block;margin-left:10px;font-size:12px;color:#DB0007;">Change</a>
            </div>
            <?php endif; ?>
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
