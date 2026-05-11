<?php
include('config/db.php'); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $pass  = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // Basic Validation
    if ($pass !== $confirm_pass) {
        $error = "Passwords do not match!";
    } else {
        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO Customer (Cust_FName, Cust_LName, Cust_Email, Cust_Password, Cust_Phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fname, $lname, $email, $hashed_pass, $phone);

        if ($stmt->execute()) {
            header("Location: login.php?success=1");
            exit();
        } else {
            $error = "Email already registered.";
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
                    <input type="text" name="phone" placeholder="+63" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>

                <div class="full-width">
                    <div class="checkbox-group">
                        <input type="checkbox" required>
                        <span>I would like to receive announcements and promotions from McDonald's.</span>
                    </div>
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