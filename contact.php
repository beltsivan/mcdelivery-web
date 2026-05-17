<?php
include('config/db.php'); 
include('includes/header.php'); 

if (!isset($_SESSION['Cust_Id'])) {
    header("Location: index.php");
    exit();
}

$cust_id = $_SESSION['Cust_Id'];
$message = "";

// --- UPDATE LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_contact'])) {
    $new_num = $_POST['contact_num'];
    $new_num_clean = preg_replace('/[^0-9]/', '', $new_num);

    if (strlen($new_num_clean) !== 11) {
        $message = "Invalid phone number. Must be exactly 11 digits.";
    } else {
        // Update the column Cust_Phone
        $sql = "UPDATE Customer SET Cust_Phone = ? WHERE Cust_Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_num_clean, $cust_id);

        if ($stmt->execute()) {
            $message = "Contact number updated successfully!";
        }
    }
}

// --- FETCH CURRENT DATA ---
$query = "SELECT Cust_Phone FROM Customer WHERE Cust_Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Store the current number to display in the input box
$current_num = $user['Cust_Phone'] ?? "";
?>

<body style="background-color: #fcfcfc;">
    <div class="profile-wrapper">
        <aside class="profile-sidebar">
            <h3>My Account</h3>
            <ul class="sidebar-menu">
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="address.php">My Address</a></li>
                <li><a href="contact.php" class="active">My Contact Number</a></li>
                <li><a href="#">Coupons</a></li>
            </ul>
        </aside>

        <main class="profile-main" style="min-height: 60vh;">
            <h2>My Contact Number</h2>

            <?php if ($message): ?>
                <div style="padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                    ✅ <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="profile-card" style="background:white; padding:40px; border-radius:15px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); max-width: 500px;">
                <form action="contact.php" method="POST">
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label style="display:block; font-size:13px; color:#666; margin-bottom: 8px; font-weight: bold;">
                            Current Mobile Number
                        </label>
                        
                        <input type="text" name="contact_num" 
                               value="<?php echo htmlspecialchars($current_num); ?>" 
                               placeholder="e.g. 09123456789" 
                               inputmode="numeric" pattern="[0-9]{11}" maxlength="11"
                               required 
                               style="width:100%; padding:15px; border:1px solid #ccc; border-radius:10px; font-size: 16px; outline: none;">
                    </div>

                    <button type="submit" name="update_contact" 
                            style="background:#FFBC0D; width:100%; padding:15px; border:none; border-radius:30px; font-weight:bold; font-size:16px; cursor:pointer; transition: 0.3s;">
                        Update Number
                    </button>
                </form>

                <p style="font-size: 12px; color: #999; margin-top: 20px; line-height: 1.4;">
                    Your contact number is used for delivery coordination. Please ensure it is active and reachable.
                </p>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

    <script>
        function showForm() {
            document.getElementById('contact-list').style.display = 'none';
            document.getElementById('contact-form').style.display = 'block';
        }
        function hideForm() {
            document.getElementById('contact-list').style.display = 'block';
            document.getElementById('contact-form').style.display = 'none';
        }
    </script>
</body>