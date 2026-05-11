<div class="manage-products-wrapper" style="display: flex; gap: 30px; align-items: flex-start;">
    <div class="product-form-container" style="flex: 1; background: white; padding: 25px; border-radius: 12px;">
        <h3>Add New Staff</h3>
        <hr>
        <?php
        // Handle add staff
        if (isset($_POST['add_staff'])) {
            $fname = trim($_POST['fname']);
            $lname = trim($_POST['lname']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $password = $_POST['password'];
            $role = $_POST['role'];
            $branchId = !empty($_POST['branch_id']) ? (int) $_POST['branch_id'] : null;

            if ($fname && $email && $password) {
                $checkStmt = mysqli_prepare($conn, "SELECT Staff_Id FROM staff WHERE Staff_Email = ? LIMIT 1");
                mysqli_stmt_bind_param($checkStmt, "s", $email);
                mysqli_stmt_execute($checkStmt);
                mysqli_stmt_store_result($checkStmt);

                if (mysqli_stmt_num_rows($checkStmt) > 0) {
                    echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Email already exists.</div>';
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO staff (Staff_Brnch_Id, Staff_Role, Staff_FName, Staff_LName, Staff_Phone, Staff_Email, Staff_Password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "issssss", $branchId, $role, $fname, $lname, $phone, $email, $password);
                    if (mysqli_stmt_execute($stmt)) {
                        echo '<div class="alert-success">Staff added successfully!</div>';
                    } else {
                        echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Error adding staff.</div>';
                    }
                    mysqli_stmt_close($stmt);
                }
                mysqli_stmt_close($checkStmt);
            } else {
                echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">First Name, Email, and Password are required.</div>';
            }
        }

        // Handle delete staff
        if (isset($_GET['delete_staff_id'])) {
            $deleteId = (int) $_GET['delete_staff_id'];
            $stmt = mysqli_prepare($conn, "DELETE FROM staff WHERE Staff_Id = ?");
            mysqli_stmt_bind_param($stmt, "i", $deleteId);
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert-success">Staff deleted successfully!</div>';
            }
            mysqli_stmt_close($stmt);
        }

        $branches = mysqli_query($conn, "SELECT * FROM mcbranch ORDER BY Brnch_City ASC");
        ?>
        <form method="POST">
            <div style="display: flex; gap: 12px;">
                <input type="text" name="fname" placeholder="First Name" required style="flex:1;">
                <input type="text" name="lname" placeholder="Last Name" style="flex:1;">
            </div>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone" placeholder="Phone (e.g. 09123456789)">
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="">-- Select Role --</option>
                <option value="Admin">Admin</option>
                <option value="Kitchen Staff">Kitchen Staff</option>
                <option value="Rider">Rider</option>
            </select>
            <select name="branch_id">
                <option value="">-- No Branch --</option>
                <?php while ($branch = mysqli_fetch_assoc($branches)): ?>
                    <option value="<?php echo $branch['Brnch_Id']; ?>">
                        <?php echo htmlspecialchars($branch['Brnch_City'] . ' - ' . $branch['Brnch_Street']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="add_staff" class="btn-admin">Add Staff</button>
        </form>
    </div>

    <div class="product-list-container" style="flex: 2; background: white; padding: 25px; border-radius: 12px;">
        <h3>Current Staff</h3>
        <hr>
        <?php
        $all_staff = mysqli_query($conn, "SELECT s.*, b.Brnch_City FROM staff s LEFT JOIN mcbranch b ON b.Brnch_Id = s.Staff_Brnch_Id ORDER BY s.Staff_Id ASC");
        ?>
        <table class="menu-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Branch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($staff = mysqli_fetch_assoc($all_staff)): ?>
                <tr>
                    <td><?php echo $staff['Staff_Id']; ?></td>
                    <td class="item-name"><?php echo htmlspecialchars($staff['Staff_FName'] . ' ' . $staff['Staff_LName']); ?></td>
                    <td><?php echo htmlspecialchars($staff['Staff_Email']); ?></td>
                    <td><span class="category-badge"><?php echo htmlspecialchars($staff['Staff_Role']); ?></span></td>
                    <td><?php echo htmlspecialchars($staff['Brnch_City'] ?? 'N/A'); ?></td>
                    <td class="item-actions">
                        <a href="admin_dashboard.php?page=staff&delete_staff_id=<?php echo $staff['Staff_Id']; ?>"
                           onclick="return confirm('Delete this staff member?');"
                           class="btn-delete">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
