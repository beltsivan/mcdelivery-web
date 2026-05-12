<div class="product-panel-container">
    <div class="add-product-card">
        <h3>Add New Menu Item</h3>
        <form action="admin_dashboard.php?page=products" method="POST" enctype="multipart/form-data">
            <input type="text" name="Menu_Name" placeholder="Product Name" required>
            <textarea name="Menu_Description" placeholder="Description"></textarea>
            <input type="number" step="0.01" name="Menu_Price" placeholder="Price" required>
            
            <select name="Menu_Category">
                <option value="Breakfast">Breakfast</option>
                <option value="Regular Menu">Regular Menu</option>
                <option value="Dinner Specials">Dinner Specials</option>
                <option value="Featured">Featured</option>
                <option value="Group Meals">Group Meals</option>
                <option value="Chicken">Chicken</option>
                <option value="Burgers">Burgers</option>
                <option value="McSpaghetti">McSpaghetti</option>
                <option value="Desserts & Drinks">Desserts & Drinks</option>
                <option value="McCafe">McCafe</option>
                <option value="Fries & Extras">Fries & Extras</option>
                <option value="Happy Meal">Happy Meal</option>
                <option value="Sulit Busog Meals">Sulit Busog Meals</option>
            </select>

            <label>Product Image:</label>
            <input type="file" name="Menu_Image" required>

            <button type="submit" name="mcdomenuitem" class="btn-primary">
                Upload Product
            </button>
        </form>
    </div>

    <div class="display-products-card">
        <h3>Current Menu Items</h3>
        <input type="text" id="menuSearch" onkeyup="filterMenuItems()" placeholder="Search menu items..." style="width:100%;padding:10px;margin-bottom:12px;border:1px solid #ddd;border-radius:8px;box-sizing:border-box;">
        <div style="max-height:500px;overflow-y:auto;">
        <table class="menu-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="menuTableBody">
                <?php while($row = mysqli_fetch_assoc($all_items)): ?>
                <tr>
                    <td class="item-name"><?php echo htmlspecialchars($row['Menu_Name']); ?></td>
                    <td>
                        <span class="category-badge">
                            <?php echo htmlspecialchars($row['Menu_Category']); ?>
                        </span>
                    </td>
                    <td class="item-price">₱<?php echo number_format($row['Menu_Price'], 2); ?></td>
                    <td class="item-actions">
                        <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                        Edit
                    </button>
                        <a href="admin_dashboard.php?page=products&delete_id=<?php echo $row['Menu_MenuItemId']; ?>" 
   onclick="return confirm('Are you sure you want to delete this item?');" 
   class="btn-delete">
   Delete
</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Edit Menu Item</h3>
        <hr>
        <form action="admin_dashboard.php?page=products" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_id" id="edit_id">
            
            <label>Product Name</label>
            <input type="text" name="Menu_Name" id="edit_name" required>
            
            <label>Description</label>
            <textarea name="Menu_Description" id="edit_desc"></textarea>
            
            <label>Price (₱)</label>
            <input type="number" step="0.01" name="Menu_Price" id="edit_price" required>
            
            <label>Category</label>
            <select name="Menu_Category" id="edit_category" required>
                <option value="" disabled selected>-- Select a Category --</option>
                <option value="Featured">Featured</option>
                <option value="Group Meals">Group Meals</option>
                <option value="Chicken">Chicken</option>
                <option value="Burgers">Burgers</option>
                <option value="McSpaghetti">McSpaghetti</option>
                <option value="Desserts & Drinks">Desserts & Drinks</option>
                <option value="McCafe">McCafe</option>
                <option value="Fries & Extras">Fries & Extras</option>
                <option value="Happy Meal">Happy Meal</option>
                <option value="Sulit Busog Meals">Sulit Busog Meals</option>
            </select>

            <label>Current Image:</label>
            <div id="current_img_container"></div>
            
            <label>Upload New Image (Optional)</label>
            <input type="file" name="Menu_Image">

            <button type="submit" name="update_product" class="btn-admin">Save Changes</button>
        </form>
    </div>
</div>
</div>

<script>
function filterMenuItems() {
    var input = document.getElementById('menuSearch');
    var filter = input.value.toLowerCase();
    var tbody = document.getElementById('menuTableBody');
    var rows = tbody.getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var name = rows[i].getElementsByClassName('item-name')[0];
        if (name) {
            var txt = name.textContent || name.innerText;
            rows[i].style.display = txt.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
        }
    }
}

function openEditModal(item) {
    document.getElementById('edit_id').value = item.Menu_MenuItemId;
    document.getElementById('edit_name').value = item.Menu_Name;
    document.getElementById('edit_desc').value = item.Menu_Description;
    document.getElementById('edit_price').value = item.Menu_Price;
    document.getElementById('edit_category').value = item.Menu_Category;
    
    // Show current image
    document.getElementById('current_img_container').innerHTML = 
        `<img src="uploads/${item.Menu_ImageURL}" style="width:80px; margin: 10px 0; border-radius:5px;">`;

    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close if clicking outside the box
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeModal();
    }
}
</script>