<?php
include('config/db.php'); 
include('includes/header.php'); 

if (!isset($_SESSION['Cust_Id'])) {
    header("Location: index.php");
    exit();
}

$cust_id = $_SESSION['Cust_Id'];

if ($firebaseInitialized) {
    $db = $firestore->database();
    $addrCol = $db->collection('addresses');

    // --- ADD ADDRESS ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['address'])) {
        $street = $_POST['Add_Street'];
        $brgy = $_POST['Add_Barangay'];
        $city = $_POST['Add_City'];
        $muni = $_POST['Add_Municipality'];
        $zip = $_POST['Add_PostalCode'];

        $addrDoc = $addrCol->add([
            'Add_Cust_Id' => $cust_id,
            'Add_Street' => $street,
            'Add_Barangay' => $brgy,
            'Add_City' => $city,
            'Add_Municipality' => $muni,
            'Add_PostalCode' => $zip,
        ]);

        $addId = $addrDoc->id();
        if (isset($_GET['from']) && $_GET['from'] === 'checkout') {
            header("Location: checkout.php?selected=$addId");
        } else {
            header("Location: address.php?success=1");
        }
        exit();
    }

    // --- DELETE ADDRESS ---
    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];
        $delDoc = $addrCol->document($delete_id)->snapshot();
        if ($delDoc->exists() && ($delDoc->data()['Add_Cust_Id'] ?? '') === $cust_id) {
            $delDoc->reference()->delete();
        }
        header("Location: address.php?msg=deleted");
        exit();
    }

    // --- FETCH ADDRESSES ---
    $snapshot = $addrCol->where('Add_Cust_Id', '=', $cust_id)->documents();
    $addresses = [];
    foreach ($snapshot as $doc) {
        if ($doc->exists()) {
            $addr = $doc->data();
            $addr['Add_Id'] = $doc->id();
            $addresses[] = $addr;
        }
    }
    $result = $addresses;
}
?>

<body style="background-color: #fcfcfc;">

    <div class="profile-wrapper">
        <aside class="profile-sidebar">
            <h3>My Account</h3>
            <ul class="sidebar-menu">
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="address.php" class="active">My Address</a></li>
                <li><a href="contact.php">My Contact Numbers</a></li>
                <li><a href="#">Coupons</a></li>
            </ul>
        </aside>

        <main class="profile-main">
    <?php if (isset($_GET['required'])): ?>
        <div class="error-banner">
            <span class="icon">&#9888;</span>
            You need to add a delivery address before you can place an order.
        </div>
    <?php endif; ?>

    <div id="address-list">
        <div class="add-btn-container">
            <button class="btn-add" onclick="showAddForm()">+ Add New Address</button>
        </div>

        <h2>My Address</h2>

        <?php if (!empty($result)): ?>
            <?php foreach ($result as $row): ?>
                <div class="address-card">
                    <div class="address-details">
                        <span class="address-tag">Address #<?php echo htmlspecialchars($row['Add_Id']); ?></span>
                        <p><?php echo htmlspecialchars($row['Add_Street']); ?>, Brgy. <?php echo htmlspecialchars($row['Add_Barangay']); ?></p>
                        <p><?php echo htmlspecialchars($row['Add_Municipality']); ?>, <?php echo htmlspecialchars($row['Add_City']); ?></p>
                        <p><?php echo htmlspecialchars($row['Add_PostalCode']); ?></p>
                    </div>
                        <a href="address.php?delete_id=<?php echo $row['Add_Id']; ?>" 
                            class="btn-delete" 
                            onclick="return confirm('Are you sure you want to delete this address?')">
                            Delete
                        </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="address-card">
                <p>No address saved yet. Add one to start ordering!</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="add-form" class="profile-card" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Add New Address</h2>
            <button type="button" onclick="hideAddForm()" style="background:none; border:none; color:#DB0007; cursor:pointer; font-weight:bold;">✕ Cancel</button>
        </div>
        
        <form action="address.php<?php echo isset($_GET['from']) ? '?from=' . urlencode($_GET['from']) : (isset($_GET['required']) ? '?from=checkout' : ''); ?>" method="POST">
            <div class="form-group">
                <label>Street / House No.</label>
                <input type="text" name="Add_Street" required>
            </div>
            <div class="form-group">
                <label>Barangay</label>
                <input type="text" name="Add_Barangay" required>
            </div>
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Municipality</label>
                    <input type="text" name="Add_Municipality" required>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="Add_City" required>
                </div>
            </div>
            <div class="form-group">
                <label>Postal Code</label>
                <input type="text" name="Add_PostalCode" required>
            </div>
            <button type="submit" name="address" class="btn-save" style="background:#FFBC0D; color:black; cursor:pointer;">
        Save Address
    </button>
        </form>
    </div>
</main>
    </div>
</body>
<?php include('includes/footer.php'); ?>
<script>
function showAddForm() {
    document.getElementById('address-list').style.display = 'none';
    document.getElementById('add-form').style.display = 'block';
}

function hideAddForm() {
    document.getElementById('address-list').style.display = 'block';
    document.getElementById('add-form').style.display = 'none';
}
</script>
