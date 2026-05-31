<?php
include('includes/header.php'); 
include('config/db.php'); 

// Redirect guest users away from this page
if (!isset($_SESSION['Cust_Id'])) {
    header("Location: index.php");
    exit();
}

$cust_id = $_SESSION['Cust_Id'];
$user = [];

if ($firebaseInitialized) {
    $db = $firestore->database();
    $customerDoc = $db->collection('customers')->document($cust_id)->snapshot();
    if ($customerDoc->exists()) {
        $user = $customerDoc->data();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/style.css">
    <style>
        
        .branch-info-card {
            background: #fff8e1;
            border: 1px solid #ffbc0d;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .branch-info-card h3 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #292929;
        }
        .branch-info-card p {
            margin: 4px 0;
            font-size: 14px;
            color: #555;
        }
        .branch-info-card .change-branch {
            display: inline-block;
            margin-top: 10px;
            font-size: 13px;
            color: #DB0007;
            text-decoration: none;
            font-weight: bold;
        }
        .branch-info-card .change-branch:hover {
            text-decoration: underline;
        }
        .no-branch {
            background: #f5f5f5;
            border: 1px dashed #ccc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .no-branch p {
            color: #888;
            font-size: 14px;
            margin: 0 0 10px;
        }
        .no-branch a {
            display: inline-block;
            background: #ffbc0d;
            color: #292929;
            padding: 10px 24px;
            border-radius: 25px;
            font-weight: bold;
            text-decoration: none;
            font-size: 14px;
        }
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
            </ul>
        </aside>

        <main class="profile-main">
            <?php if (isset($_SESSION['Cust_Brnch_Id'])): ?>
            <div class="branch-info-card">
                <h3>&#127963; Your Branch: <?php echo htmlspecialchars($_SESSION['Cust_Brnch_Name'] ?? ''); ?></h3>
                <p>
                    <?php
                    $addr = [];
                    if (!empty($_SESSION['Cust_Brnch_Street'])) $addr[] = $_SESSION['Cust_Brnch_Street'];
                    if (!empty($_SESSION['Cust_Brnch_Barangay'])) $addr[] = 'Brgy. ' . $_SESSION['Cust_Brnch_Barangay'];
                    if (!empty($_SESSION['Cust_Brnch_City'])) $addr[] = $_SESSION['Cust_Brnch_City'];
                    if (!empty($_SESSION['Cust_Brnch_Municipality'])) $addr[] = $_SESSION['Cust_Brnch_Municipality'];
                    if (!empty($_SESSION['Cust_Brnch_PostalCode'])) $addr[] = $_SESSION['Cust_Brnch_PostalCode'];
                    echo htmlspecialchars(implode(', ', $addr));
                    ?>
                </p>
                <a href="branch_select.php" class="change-branch">Change Branch</a>
            </div>
            <?php else: ?>
            <div class="no-branch">
                <p>You haven't selected a branch yet.</p>
                <a href="branch_select.php">Select a Branch</a>
            </div>
            <?php endif; ?>

            <div class="profile-card">
                <h2>My Profile</h2>
                
                <form action="update_profile.php" method="POST">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="fname" value="<?php echo $user['Cust_FName'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lname" value="<?php echo $user['Cust_LName'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo $user['Cust_Email'] ?? ''; ?>" readonly style="color: #aaa;">
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
