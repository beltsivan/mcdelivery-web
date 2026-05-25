<?php
if ($firebaseInitialized) {
    $db = $firestore->database();

    // Handle add branch
    if (isset($_POST['add_branch'])) {
        $name = trim($_POST['name']);
        $street = trim($_POST['street']);
        $barangay = trim($_POST['barangay']);
        $city = trim($_POST['city']);
        $municipality = trim($_POST['municipality']);
        $postal = preg_replace('/[^0-9]/', '', trim($_POST['postal']));
        $phone = trim($_POST['phone']);
        $phone_clean = preg_replace('/[^0-9]/', '', $phone);
        $error = null;

        if (strlen($phone_clean) > 0 && strlen($phone_clean) !== 11) {
            $error = 'Phone number must be exactly 11 digits.';
        }

        if ($name && $city && !$error) {
            $db->collection('branches')->add([
                'Brnch_Name' => $name,
                'Brnch_Street' => $street,
                'Brnch_Barangay' => $barangay,
                'Brnch_City' => $city,
                'Brnch_Municipality' => $municipality,
                'Brnch_PostalCode' => $postal,
                'Brnch_Phone' => strlen($phone_clean) ? $phone_clean : null,
            ]);
            echo '<div class="alert-success">Branch added successfully!</div>';
        } elseif ($name && $city && $error) {
            echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">' . $error . '</div>';
        } else {
            echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Branch Name and City are required.</div>';
        }
    }

    // Handle edit branch
    if (isset($_POST['edit_branch'])) {
        $branchId = $_POST['branch_id'];
        $name = trim($_POST['name']);
        $street = trim($_POST['street']);
        $barangay = trim($_POST['barangay']);
        $city = trim($_POST['city']);
        $municipality = trim($_POST['municipality']);
        $postal = preg_replace('/[^0-9]/', '', trim($_POST['postal']));
        $phone = trim($_POST['phone']);
        $phone_clean = preg_replace('/[^0-9]/', '', $phone);
        $error = null;

        if (strlen($phone_clean) > 0 && strlen($phone_clean) !== 11) {
            $error = 'Phone number must be exactly 11 digits.';
        }

        if ($name && $city && !$error && $branchId) {
            $db->collection('branches')->document($branchId)->set([
                'Brnch_Name' => $name,
                'Brnch_Street' => $street,
                'Brnch_Barangay' => $barangay,
                'Brnch_City' => $city,
                'Brnch_Municipality' => $municipality,
                'Brnch_PostalCode' => $postal,
                'Brnch_Phone' => strlen($phone_clean) ? $phone_clean : null,
            ], ['merge' => true]);
            echo '<div class="alert-success">Branch updated successfully!</div>';
        } elseif ($name && $city && $error) {
            echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">' . $error . '</div>';
        } else {
            echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Branch Name and City are required.</div>';
        }
    }

    // Handle delete branch
    if (isset($_GET['delete_branch_id'])) {
        $deleteId = $_GET['delete_branch_id'];
        // Check for staff assigned to branch
        $staffDocs = $db->collection('staff')->where('Staff_Brnch_Id', '=', $deleteId)->documents();
        $staffCount = 0;
        foreach ($staffDocs as $s) { if ($s->exists()) $staffCount++; }

        if ($staffCount > 0) {
            echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Cannot delete branch: ' . $staffCount . ' staff member(s) are assigned to it.</div>';
        } else {
            $db->collection('branches')->document($deleteId)->delete();
            echo '<div class="alert-success">Branch deleted successfully!</div>';
        }
    }
}
?>

<?php if ($staffRole === 'Manager'): ?>
<div class="manage-products-wrapper" style="display: flex; gap: 30px; align-items: flex-start;">
    <div class="product-form-container" style="flex: 1; background: white; padding: 25px; border-radius: 12px;">
        <h3>Add New Staff</h3>
        <hr>
        <?php
        if ($firebaseInitialized) {
            // Handle add staff
            if (isset($_POST['add_staff'])) {
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
                $role = $_POST['role'];
                $branchId = !empty($_POST['branch_id']) ? $_POST['branch_id'] : null;

                if ($fname && $email && $password) {
                    try {
                        // Check if email already exists
                        try {
                            $auth->getUserByEmail($email);
                            echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Email already exists.</div>';
                        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                            // Create Firebase Auth user
                            $user = $auth->createUserWithEmailAndPassword($email, $password);
                            $uid = $user->uid;

                            // Create staff doc
                            $db->collection('staff')->document($uid)->set([
                                'Staff_Brnch_Id' => $branchId,
                                'Staff_Role' => $role,
                                'Staff_FName' => $fname,
                                'Staff_LName' => $lname,
                                'Staff_Phone' => $phone_clean,
                                'Staff_Email' => $email,
                            ]);
                            echo '<div class="alert-success">Staff added successfully!</div>';
                        }
                    } catch (\Exception $e) {
                        echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Error adding staff.</div>';
                    }
                } else {
                    echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">First Name, Email, and Password are required.</div>';
                }
            }

            // Handle delete staff
            if (isset($_GET['delete_staff_id'])) {
                $deleteId = $_GET['delete_staff_id'];
                try {
                    // Delete from Firebase Auth
                    $auth->deleteUser($deleteId);
                    // Delete from Firestore
                    $db->collection('staff')->document($deleteId)->delete();
                    echo '<div class="alert-success">Staff deleted successfully!</div>';
                } catch (\Exception $e) {
                    echo '<div class="alert-success" style="background:#f8d7da;color:#721c24;">Error deleting staff.</div>';
                }
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
            <select name="role" required>
                <option value="">-- Select Role --</option>
                <option value="Kitchen Staff">Kitchen Staff</option>
                <option value="Rider">Rider</option>
            </select>
            <?php if ($staffRole === 'Manager' && isset($_SESSION['Staff_Brnch_Id'])): ?>
                <input type="hidden" name="branch_id" value="<?php echo $_SESSION['Staff_Brnch_Id']; ?>">
            <?php else: ?>
            <select name="branch_id">
                <option value="">-- No Branch --</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch['Brnch_Id']; ?>">
                        <?php echo htmlspecialchars(($branch['Brnch_Name'] ? $branch['Brnch_Name'] . ' - ' : '') . $branch['Brnch_City'] . ' - ' . $branch['Brnch_Street']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <button type="submit" name="add_staff" class="btn-admin">Add Staff</button>
        </form>
    </div>

    <div class="product-list-container" style="flex: 2; background: white; padding: 25px; border-radius: 12px;">
        <h3>Current Staff</h3>
        <input type="text" id="staffSearch" onkeyup="filterStaff()" placeholder="Search staff..." style="width:100%;padding:10px;margin-bottom:12px;border:1px solid #ddd;border-radius:8px;box-sizing:border-box;">
        <hr>
        <?php
        $staffBranchFilter = ($staffRole === 'Manager' && isset($_SESSION['Staff_Brnch_Id'])) ? $_SESSION['Staff_Brnch_Id'] : null;
        $staffQuery = $db->collection('staff');
        if ($staffBranchFilter) {
            $staffQuery = $staffQuery->where('Staff_Brnch_Id', '=', $staffBranchFilter);
        }
        $staffSnapshot = $staffQuery->documents();
        $all_staff = [];
        foreach ($staffSnapshot as $sDoc) {
            if (!$sDoc->exists()) continue;
            $s = $sDoc->data();
            $s['Staff_Id'] = $sDoc->id();
            // Denormalize branch name
            if (!empty($s['Staff_Brnch_Id'])) {
                $bDoc = $db->collection('branches')->document($s['Staff_Brnch_Id'])->snapshot();
                if ($bDoc->exists()) {
                    $bData = $bDoc->data();
                    $s['Brnch_Name'] = $bData['Brnch_Name'] ?? '';
                    $s['Brnch_City'] = $bData['Brnch_City'] ?? '';
                }
            }
            $all_staff[] = $s;
        }
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
                <?php foreach ($all_staff as $staff): ?>
                <tr>
                    <td><?php echo htmlspecialchars(substr($staff['Staff_Id'], 0, 8)) . '...'; ?></td>
                    <td class="item-name"><?php echo htmlspecialchars(($staff['Staff_FName'] ?? '') . ' ' . ($staff['Staff_LName'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($staff['Staff_Email'] ?? ''); ?></td>
                    <td><span class="category-badge"><?php echo htmlspecialchars($staff['Staff_Role'] ?? ''); ?></span></td>
                    <td><?php echo htmlspecialchars(($staff['Brnch_Name'] ?? '') ? $staff['Brnch_Name'] . ' - ' . ($staff['Brnch_City'] ?? '') : ($staff['Brnch_City'] ?? 'N/A')); ?></td>
                    <td class="item-actions">
                        <a href="admin_dashboard.php?page=staff&delete_staff_id=<?php echo $staff['Staff_Id']; ?>"
                           onclick="return confirm('Delete this staff member?');"
                           class="btn-delete">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($isSystemAdmin): ?>
<div class="display-products-card" style="margin-top: 30px;">
    <div class="card-header">
        <h3>Manage Branches</h3>
        <span class="item-count"><?php
            $branchCount = 0;
            foreach ($db->collection('branches')->documents() as $d) { if ($d->exists()) $branchCount++; }
            echo $branchCount; ?> branches
        </span>
    </div>
    <div class="search-bar">
        <span class="search-icon">&#128269;</span>
        <input type="text" id="branchSearch" onkeyup="filterBranches()" placeholder="Search branches...">
    </div>
    <div class="table-wrap">
        <table class="menu-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Street</th>
                    <th>Barangay</th>
                    <th>City</th>
                    <th>Municipality</th>
                    <th>Postal Code</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $braSnapshot = $db->collection('branches')->orderBy('Brnch_Name')->documents();
                foreach ($braSnapshot as $branchDoc):
                    if (!$branchDoc->exists()) continue;
                    $branch = $branchDoc->data();
                    $branch['Brnch_Id'] = $branchDoc->id();
                ?>
                <tr>
                    <td><?php echo htmlspecialchars(substr($branch['Brnch_Id'], 0, 8)) . '...'; ?></td>
                    <td class="item-name"><?php echo htmlspecialchars($branch['Brnch_Name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($branch['Brnch_Street'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($branch['Brnch_Barangay'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($branch['Brnch_City'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($branch['Brnch_Municipality'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($branch['Brnch_PostalCode'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($branch['Brnch_Phone'] ?? ''); ?></td>
                    <td class="item-actions">
                        <a href="javascript:void(0)" onclick='openEditBranchModal(<?php echo json_encode($branch, JSON_HEX_APOS); ?>)' class="btn-edit">Edit</a>
                        <a href="admin_dashboard.php?page=staff&delete_branch_id=<?php echo $branch['Brnch_Id']; ?>"
                           onclick="return confirm('Delete this branch?');"
                           class="btn-delete">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="add-bottom" onclick="openBranchModal()">
        <span class="add-bottom-icon">+</span>
        Add Branch
    </div>
</div>

<!-- Add Branch Modal -->
<div id="branchModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-btn" onclick="closeBranchModal()">&times;</span>
        <h3>Add New Branch</h3>
        <hr>
        <form method="POST">
            <input type="text" name="name" placeholder="Branch Name" required>
            <input type="text" name="street" placeholder="Street" required>
            <input type="text" name="barangay" placeholder="Barangay">
            <input type="text" name="city" placeholder="City" required>
            <input type="text" name="municipality" placeholder="Municipality">
            <input type="text" name="postal" placeholder="Postal Code" inputmode="numeric" pattern="[0-9]*">
            <input type="text" name="phone" placeholder="Phone (e.g. 09123456789)" inputmode="numeric" pattern="[0-9]{11}" maxlength="11">
            <button type="submit" name="add_branch" class="btn-admin">Add Branch</button>
        </form>
    </div>
</div>

<!-- Edit Branch Modal -->
<div id="editBranchModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-btn" onclick="closeEditBranchModal()">&times;</span>
        <h3>Edit Branch</h3>
        <hr>
        <form method="POST">
            <input type="hidden" name="branch_id" id="edit_branch_id" value="">
            <input type="text" name="name" id="edit_name" placeholder="Branch Name" required>
            <input type="text" name="street" id="edit_street" placeholder="Street" required>
            <input type="text" name="barangay" id="edit_barangay" placeholder="Barangay">
            <input type="text" name="city" id="edit_city" placeholder="City" required>
            <input type="text" name="municipality" id="edit_municipality" placeholder="Municipality">
            <input type="text" name="postal" id="edit_postal" placeholder="Postal Code" inputmode="numeric" pattern="[0-9]*">
            <input type="text" name="phone" id="edit_phone" placeholder="Phone (e.g. 09123456789)" inputmode="numeric" pattern="[0-9]{11}" maxlength="11">
            <button type="submit" name="edit_branch" class="btn-admin">Update Branch</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function filterStaff() {
    var input = document.getElementById('staffSearch');
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

function filterBranches() {
    var input = document.getElementById('branchSearch');
    var filter = input.value.toLowerCase();
    var table = document.querySelector('.display-products-card .menu-table tbody');
    if (!table) return;
    var rows = table.getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var text = rows[i].textContent || rows[i].innerText;
        rows[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

function openBranchModal() {
    document.getElementById('branchModal').style.display = 'block';
}

function closeBranchModal() {
    document.getElementById('branchModal').style.display = 'none';
}

function openEditBranchModal(branch) {
    document.getElementById('edit_branch_id').value = branch.Brnch_Id;
    document.getElementById('edit_name').value = branch.Brnch_Name || '';
    document.getElementById('edit_street').value = branch.Brnch_Street || '';
    document.getElementById('edit_barangay').value = branch.Brnch_Barangay || '';
    document.getElementById('edit_city').value = branch.Brnch_City || '';
    document.getElementById('edit_municipality').value = branch.Brnch_Municipality || '';
    document.getElementById('edit_postal').value = branch.Brnch_PostalCode || '';
    document.getElementById('edit_phone').value = branch.Brnch_Phone || '';
    document.getElementById('editBranchModal').style.display = 'block';
}

function closeEditBranchModal() {
    document.getElementById('editBranchModal').style.display = 'none';
}
</script>
