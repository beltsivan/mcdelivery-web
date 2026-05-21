<div class="manage-products-wrapper" style="display: flex; gap: 30px; align-items: flex-start;">
    <div class="product-form-container" style="flex: 1; background: white; padding: 25px; border-radius: 12px;">
        <h3>Add New Manager</h3>
        <hr>
        <?php
        // Handle add manager
        if (isset($_POST['add_manager'])) {
            $fname = trim($_POST['fname']);
            $lname = trim($_POST['lname']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $phone_clean = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone_clean) === 0) {
                $phone_clean = null;
            } elseif (strlen($phone_clean) !== 11) {
                echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Phone number must be exactly 11 digits.</div>';
                $phone_clean = null;
            }
            $password = $_POST['password'];
            $branchId = !empty($_POST['branch_id']) ? (int) $_POST['branch_id'] : null;
            $role = 'Manager';

            if ($fname && $email && $password && $branchId) {
                $checkStmt = mysqli_prepare($conn, "SELECT Staff_Id FROM staff WHERE Staff_Email = ? LIMIT 1");
                mysqli_stmt_bind_param($checkStmt, "s", $email);
                mysqli_stmt_execute($checkStmt);
                mysqli_stmt_store_result($checkStmt);

                if (mysqli_stmt_num_rows($checkStmt) > 0) {
                    echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Email already exists.</div>';
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO staff (Staff_Brnch_Id, Staff_Role, Staff_FName, Staff_LName, Staff_Phone, Staff_Email, Staff_Password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "issssss", $branchId, $role, $fname, $lname, $phone_clean, $email, $password);
                    if (mysqli_stmt_execute($stmt)) {
                        echo '<div class="alert-success">Manager added successfully!</div>';
                    } else {
                        echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Error adding manager.</div>';
                    }
                    mysqli_stmt_close($stmt);
                }
                mysqli_stmt_close($checkStmt);
            } else {
                echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">All fields including Branch are required.</div>';
            }
        }

        // Handle delete manager
        if (isset($_GET['delete_manager_id'])) {
            $deleteId = (int) $_GET['delete_manager_id'];
            $stmt = mysqli_prepare($conn, "DELETE FROM staff WHERE Staff_Id = ? AND Staff_Role = 'Manager'");
            mysqli_stmt_bind_param($stmt, "i", $deleteId);
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert-success">Manager deleted successfully!</div>';
            }
            mysqli_stmt_close($stmt);
        }

        $branches = mysqli_query($conn, "SELECT * FROM mcbranch ORDER BY Brnch_Name ASC, Brnch_City ASC");
        ?>
        <form method="POST">
            <div style="display: flex; gap: 12px;">
                <input type="text" name="fname" placeholder="First Name" required style="flex:1;">
                <input type="text" name="lname" placeholder="Last Name" style="flex:1;">
            </div>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone" placeholder="Phone (e.g. 09123456789)" inputmode="numeric" pattern="[0-9]{11}" maxlength="11">
            <input type="password" name="password" placeholder="Password" required>
            <select name="branch_id" required>
                <option value="">-- Select Branch --</option>
                <?php while ($branch = mysqli_fetch_assoc($branches)): ?>
                    <option value="<?php echo $branch['Brnch_Id']; ?>">
                        <?php echo htmlspecialchars(($branch['Brnch_Name'] ? $branch['Brnch_Name'] . ' - ' : '') . $branch['Brnch_City'] . ' - ' . $branch['Brnch_Street']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="add_manager" class="btn-admin">Add Manager</button>
        </form>
    </div>

    <div class="product-list-container" style="flex: 2; background: white; padding: 25px; border-radius: 12px;">
        <h3>Current Managers</h3>
        <input type="text" id="managerSearch" onkeyup="filterManagers()" placeholder="Search managers..." style="width:100%;padding:10px;margin-bottom:12px;border:1px solid #ddd;border-radius:8px;box-sizing:border-box;">
        <hr>
        <?php
        $all_managers = mysqli_query($conn, "SELECT s.*, b.Brnch_Name, b.Brnch_City, b.Brnch_Street FROM staff s LEFT JOIN mcbranch b ON b.Brnch_Id = s.Staff_Brnch_Id WHERE s.Staff_Role = 'Manager' ORDER BY s.Staff_Id ASC");
        ?>
        <table class="menu-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Branch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($mgr = mysqli_fetch_assoc($all_managers)): ?>
                <tr>
                    <td><?php echo $mgr['Staff_Id']; ?></td>
                    <td class="item-name"><?php echo htmlspecialchars($mgr['Staff_FName'] . ' ' . $mgr['Staff_LName']); ?></td>
                    <td><?php echo htmlspecialchars($mgr['Staff_Email']); ?></td>
                    <td><?php echo htmlspecialchars(($mgr['Brnch_Name'] ?? '') ? $mgr['Brnch_Name'] . ' - ' . ($mgr['Brnch_City'] ?? '') : ($mgr['Brnch_City'] ?? 'N/A')); ?></td>
                    <td class="item-actions">
                        <a href="admin_dashboard.php?page=managers&delete_manager_id=<?php echo $mgr['Staff_Id']; ?>"
                           onclick="return confirm('Delete this manager?');"
                           class="btn-delete">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterManagers() {
    var input = document.getElementById('managerSearch');
    var filter = input.value.toLowerCase();
    var tbody = document.querySelector('.product-list-container tbody');
    if (!tbody) return;
    var rows = tbody.getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName('td');
        var found = false;
        for (var j = 0; j < cells.length; j++) {
            var txt = cells[j].textContent || cells[j].innerText;
            if (txt.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        rows[i].style.display = found ? '' : 'none';
    }
}
</script>
