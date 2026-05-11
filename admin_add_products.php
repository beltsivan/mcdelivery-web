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
        <table class="menu-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
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
                        <a href="admin_delete_product.php?id=<?php echo $row['Menu_MenuItemId']; ?>" 
                           class="btn-delete" 
                           onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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