<div class="manage-products-wrapper" style="display: flex; gap: 30px; align-items: flex-start;">
    <div class="product-form-container" style="flex: 1; background: white; padding: 25px; border-radius: 12px;">
        <h3>Add New Manager</h3>
        <hr>
        <?php
        if ($firebaseInitialized) {
            $db = $firestore->database();

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
                $branchId = !empty($_POST['branch_id']) ? $_POST['branch_id'] : null;
                $role = 'Manager';

                if ($fname && $email && $password && $branchId) {
                    try {
                        try {
                            $auth->getUserByEmail($email);
                            echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Email already exists.</div>';
                        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                            $user = $auth->createUserWithEmailAndPassword($email, $password);
                            $uid = $user->uid;

                            $db->collection('staff')->document($uid)->set([
                                'Staff_Brnch_Id' => $branchId,
                                'Staff_Role' => $role,
                                'Staff_FName' => $fname,
                                'Staff_LName' => $lname,
                                'Staff_Phone' => $phone_clean,
                                'Staff_Email' => $email,
                            ]);
                            echo '<div class="alert-success">Manager added successfully!</div>';
                        }
                    } catch (\Exception $e) {
                        echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Error adding manager.</div>';
                    }
                } else {
                    echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">All fields including Branch are required.</div>';
                }
            }

            // Handle delete manager
            if (isset($_GET['delete_manager_id'])) {
                $deleteId = $_GET['delete_manager_id'];
                try {
                    // Verify it's a manager
                    $staffDoc = $db->collection('staff')->document($deleteId)->snapshot();
                    if ($staffDoc->exists() && ($staffDoc->data()['Staff_Role'] ?? '') === 'Manager') {
                        $auth->deleteUser($deleteId);
                        $db->collection('staff')->document($deleteId)->delete();
                        echo '<div class="alert-success">Manager deleted successfully!</div>';
                    }
                } catch (\Exception $e) {
                    echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Error deleting manager.</div>';
                }
            }

            $branchDocs = $db->collection('branches')->orderBy('Brnch_Name')->documents();
            $branches = [];
            foreach ($branchDocs as $doc) {
                if ($doc->exists()) {
                    $b = $doc->data();
                    $b['Brnch_Id'] = $doc->id();
                    $branches[] = $b;
                }
            }
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
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['Brnch_Id']; ?>">
                            <?php echo htmlspecialchars(($branch['Brnch_Name'] ? $branch['Brnch_Name'] . ' - ' : '') . $branch['Brnch_City'] . ' - ' . $branch['Brnch_Street']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_manager" class="btn-admin">Add Manager</button>
            </form>
        <?php } ?>
    </div>

    <div class="product-list-container" style="flex: 2; background: white; padding: 25px; border-radius: 12px;">
        <h3>Current Managers</h3>
        <input type="text" id="managerSearch" onkeyup="filterManagers()" placeholder="Search managers..." style="width:100%;padding:10px;margin-bottom:12px;border:1px solid #ddd;border-radius:8px;box-sizing:border-box;">
        <hr>
        <?php
        if ($firebaseInitialized) {
            $mgSnapshot = $db->collection('staff')
                ->where('Staff_Role', '=', 'Manager')
                ->documents();
            $all_managers = [];
            foreach ($mgSnapshot as $mDoc) {
                if (!$mDoc->exists()) continue;
                $m = $mDoc->data();
                $m['Staff_Id'] = $mDoc->id();
                // Denormalize branch name
                if (!empty($m['Staff_Brnch_Id'])) {
                    $bDoc = $db->collection('branches')->document($m['Staff_Brnch_Id'])->snapshot();
                    if ($bDoc->exists()) {
                        $bData = $bDoc->data();
                        $m['Brnch_Name'] = $bData['Brnch_Name'] ?? '';
                        $m['Brnch_City'] = $bData['Brnch_City'] ?? '';
                        $m['Brnch_Street'] = $bData['Brnch_Street'] ?? '';
                    }
                }
                $all_managers[] = $m;
            }
        }
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
                <?php foreach ($all_managers as $mgr): ?>
                <tr>
                    <td><?php echo htmlspecialchars(substr($mgr['Staff_Id'], 0, 8)) . '...'; ?></td>
                    <td class="item-name"><?php echo htmlspecialchars(($mgr['Staff_FName'] ?? '') . ' ' . ($mgr['Staff_LName'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($mgr['Staff_Email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(($mgr['Brnch_Name'] ?? '') ? $mgr['Brnch_Name'] . ' - ' . ($mgr['Brnch_City'] ?? '') : ($mgr['Brnch_City'] ?? 'N/A')); ?></td>
                    <td class="item-actions">
                        <a href="admin_dashboard.php?page=managers&delete_manager_id=<?php echo $mgr['Staff_Id']; ?>"
                           onclick="return confirm('Delete this manager?');"
                           class="btn-delete">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
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
