<?php
include('config/db.php'); 
include('includes/header.php'); 

if (!isset($_SESSION['Cust_Id'])) {
    header("Location: index.php");
    exit();
}

$cust_id = $_SESSION['Cust_Id'];

// --- ADD THIS LOGIC HERE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['address'])) {
    $cust_id = $_SESSION['Cust_Id'];
    $street = $_POST['Add_Street'];
    $brgy = $_POST['Add_Barangay'];
    $city = $_POST['Add_City'];
    $muni = $_POST['Add_Municipality'];
    $zip = $_POST['Add_PostalCode'];

    $sql = "INSERT INTO Address (Add_Cust_Id, Add_Street, Add_Barangay, Add_City, Add_Municipality, Add_PostalCode) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $cust_id, $street, $brgy, $city, $muni, $zip);

    if ($stmt->execute()) {
        // Redirect to the same page to "refresh" the list and clear the POST data
        header("Location: address.php?success=1");
        exit();
    }
}

// --- DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $cust_id = $_SESSION['Cust_Id']; // Security: Ensure it belongs to the logged-in user

    // We include Add_Cust_Id in the WHERE clause so users can't delete other people's addresses by guessing IDs
    $sql = "DELETE FROM Address WHERE Add_Id = ? AND Add_Cust_Id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $delete_id, $cust_id);

    if ($stmt->execute()) {
        // Redirect back to the clean URL to remove the ?delete_id from the address bar
        header("Location: address.php?msg=deleted");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

$cust_id = $_SESSION['Cust_Id'];
$query = "SELECT * FROM Address WHERE Add_Cust_Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$result = $stmt->get_result();
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
    <div id="address-list">
        <div class="add-btn-container">
            <button class="btn-add" onclick="showAddForm()">+ Add New Address</button>
        </div>

        <h2>My Address</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="address-card">
                    <div class="address-details">
                        <span class="address-tag">Address #<?php echo $row['Add_Id']; ?></span>
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
            <?php endwhile; ?>
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
        
        <form action="address.php" method="POST">
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
