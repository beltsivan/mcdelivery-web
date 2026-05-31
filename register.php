<?php
include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $pass  = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    $phone_clean = preg_replace('/[^0-9]/', '', $phone);

    if (strlen($phone_clean) !== 11) {
        $error = "Invalid phone number format. Must be exactly 11 digits.";
    } elseif ($pass !== $confirm_pass) {
        $error = "Passwords do not match!";
    } elseif (!$firebaseInitialized) {
        $error = "Firebase not configured.";
    } else {
        try {
            // Check if email already exists via Firebase Auth
            try {
                $existingUser = $auth->getUserByEmail($email);
                $error = "Email is already used.";
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                // Email not taken, proceed with registration
                $user = $auth->createUserWithEmailAndPassword($email, $pass);
                $uid = $user->uid;

                // Store customer data in Firestore
                $db = $firestore->database();
                $db->collection('customers')->document($uid)->set([
                    'Cust_Id' => $uid,
                    'Cust_FName' => $fname,
                    'Cust_LName' => $lname,
                    'Cust_Email' => $email,
                    'Cust_Phone' => $phone_clean,
                    'Cust_CreatedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                ]);

                session_start();
                $_SESSION['reg_success'] = true;
                $_SESSION['show_login_modal'] = true;
                header("Location: index.php");
                exit();
            }
        } catch (\Exception $e) {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="reg-container">
        <h1>Ready to sign up to McDelivery?</h1>
        <p>Tell us more about you so we can give you a better delivery experience.</p>

        <div class="reg-card">
            <?php if (isset($error)): ?>
                <div class="error-banner">
                    <span class="icon">&#9888;</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form action="register.php" method="POST" id="regForm">
                <span class="section-title">User Details</span>
                <div class="form-grid">
                    <input type="text" name="fname" placeholder="First Name" required>
                    <input type="text" name="lname" placeholder="Last Name" required>
                </div>

                <br>
                <span class="section-title">Login & Contact Details</span>
                <div class="form-grid">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="phone" placeholder="e.g. 09123456789" inputmode="numeric" pattern="[0-9]{11}" maxlength="11" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>

                <div class="full-width">
                    <div class="checkbox-group">
                        <input type="checkbox" required>
                        <span>I consent to the use and processing of my personal information...</span>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" required>
                        <span>I have fully read, understood, and agree to the <a href="#" style="color:#DB0007">Data Privacy Policy</a>.</span>
                    </div>
                </div>

                <button type="submit" class="btn-create" id="submitBtn">Create your Account</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php include('includes/footer.php'); ?>
<script>

</script>
