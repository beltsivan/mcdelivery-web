<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['Cust_Id'])) {
    header('Location: login.php');
    exit;
}

// Handle branch selection
if (isset($_POST['branch_id'])) {
    $branchId = (int) $_POST['branch_id'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM mcbranch WHERE Brnch_Id = ?");
    mysqli_stmt_bind_param($stmt, "i", $branchId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $branch = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($branch) {
        $_SESSION['Cust_Brnch_Id'] = (int) $branch['Brnch_Id'];
        $_SESSION['Cust_Brnch_Name'] = $branch['Brnch_Name'];
        $_SESSION['Cust_Brnch_Street'] = $branch['Brnch_Street'];
        $_SESSION['Cust_Brnch_Barangay'] = $branch['Brnch_Barangay'];
        $_SESSION['Cust_Brnch_City'] = $branch['Brnch_City'];
        $_SESSION['Cust_Brnch_Municipality'] = $branch['Brnch_Municipality'];
        $_SESSION['Cust_Brnch_PostalCode'] = $branch['Brnch_PostalCode'];
        header('Location: index.php');
        exit;
    }
}

$currentBranchId = isset($_SESSION['Cust_Brnch_Id']) ? (int) $_SESSION['Cust_Brnch_Id'] : null;
$branches = mysqli_query($conn, "SELECT * FROM mcbranch ORDER BY Brnch_Name ASC, Brnch_City ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Branch - McDelivery</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #f4f4f4;
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .branch-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .branch-container h1 {
            color: #292929;
            font-size: 26px;
            margin: 0 0 8px;
        }
        .branch-container .subtitle {
            color: #777;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .branch-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .branch-option {
            border: 2px solid #e5e5e5;
            border-radius: 12px;
            padding: 18px 20px;
            text-align: left;
            cursor: pointer;
            transition: 0.2s;
            display: block;
            width: 100%;
            background: none;
            font-family: inherit;
            font-size: inherit;
        }
        .branch-option:hover {
            border-color: #ffbc0d;
            background: #fffbf0;
        }
        .branch-option.selected {
            border-color: #ffbc0d;
            background: #fffbf0;
        }
        .branch-option .branch-name {
            font-weight: bold;
            font-size: 16px;
            color: #292929;
        }
        .branch-option .branch-address {
            font-size: 13px;
            color: #888;
            margin-top: 4px;
        }
        .btn-confirm {
            background: #ffbc0d;
            border: none;
            padding: 14px 40px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            color: #292929;
            margin-top: 24px;
            width: 100%;
            transition: 0.2s;
        }
        .btn-confirm:hover {
            background: #f0a800;
        }
        .btn-confirm:disabled {
            background: #ddd;
            color: #aaa;
            cursor: not-allowed;
        }
        .skip-link {
            display: block;
            margin-top: 16px;
            color: #888;
            font-size: 13px;
            text-decoration: none;
        }
        .skip-link:hover {
            color: #292929;
        }
        .branch-logo {
            width: 80px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="branch-container">
        <img src="images/mcdologo.png" alt="McDonald's" class="branch-logo">
        <h1>Select Your Branch</h1>
        <p class="subtitle">Choose the McDonald's branch you're ordering from today.</p>

        <form method="POST" id="branchForm">
            <div class="branch-list">
                <?php while ($branch = mysqli_fetch_assoc($branches)): ?>
                <button type="button" class="branch-option<?php echo ($currentBranchId === (int) $branch['Brnch_Id']) ? ' selected' : ''; ?>" onclick="selectBranch(this, <?php echo $branch['Brnch_Id']; ?>)">
                    <div class="branch-name"><?php echo htmlspecialchars($branch['Brnch_Name'] ?: $branch['Brnch_City'] . ' - ' . $branch['Brnch_Street']); ?></div>
                    <div class="branch-address">
                        <?php
                        $addr = [];
                        if ($branch['Brnch_Street']) $addr[] = $branch['Brnch_Street'];
                        if ($branch['Brnch_Barangay']) $addr[] = 'Brgy. ' . $branch['Brnch_Barangay'];
                        if ($branch['Brnch_City']) $addr[] = $branch['Brnch_City'];
                        if ($branch['Brnch_Municipality']) $addr[] = $branch['Brnch_Municipality'];
                        if ($branch['Brnch_PostalCode']) $addr[] = $branch['Brnch_PostalCode'];
                        echo htmlspecialchars(implode(', ', $addr));
                        ?>
                    </div>
                </button>
                <?php endwhile; ?>
            </div>
            <input type="hidden" name="branch_id" id="selectedBranchId" value="">
            <button type="submit" class="btn-confirm" id="confirmBtn" disabled>Confirm Branch</button>
        </form>

        <a href="index.php" class="skip-link">Skip, I'll choose later</a>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var selected = document.querySelector('.branch-option.selected');
        if (selected) {
            var bid = selected.getAttribute('onclick').match(/\d+/)[0];
            document.getElementById('selectedBranchId').value = bid;
            document.getElementById('confirmBtn').disabled = false;
        }
    });

    function selectBranch(el, branchId) {
        document.querySelectorAll('.branch-option').forEach(function(opt) {
            opt.classList.remove('selected');
        });
        el.classList.add('selected');
        document.getElementById('selectedBranchId').value = branchId;
        document.getElementById('confirmBtn').disabled = false;
    }
    </script>
</body>
</html>
