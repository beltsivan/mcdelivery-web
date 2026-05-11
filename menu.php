<?php include('includes/header.php'); 
include('config/db.php'); 
require_once('includes/cart.php');

$category_map = [
    'Featured'          => "Menu_Category = 'Featured'",
    'Group Meals'       => "Menu_Category = 'Dinner Special'", // Maps 'Group Meals' to 'Dinner Special'
    'Chicken'           => "Menu_Category IN ('Chicken', 'Exclusives')", // Combines two DB categories
    'Burgers'           => "Menu_Category = 'Burgers'",
    'McSpaghetti'       => "Menu_Category = 'Pasta'", // Change 'Pasta' to whatever your DB uses
    'Desserts & Drinks' => "Menu_Category IN ('Dessert', 'Drinks')",
    'McCafe'            => "Menu_Category = 'McCafe'",
    'Fries & Extras'    => "Menu_Category = 'Sides'",
    'Happy Meal'        => "Menu_Category = 'Happy Meal'",
    'Sulit Busog Meals' => "Menu_MenuItemId IN (10, 11, 12)" // Or filter by specific IDs
];

// Get the category from URL, default to Featured
$nav_cat = isset($_GET['category']) ? $_GET['category'] : 'Featured';

// Use the map to get the SQL filter, or fallback to a default
$sql_filter = isset($category_map[$nav_cat]) ? $category_map[$nav_cat] : "Menu_Category = '$nav_cat'";

$query = "SELECT * FROM McdoMenuItem WHERE $sql_filter AND Menu_Available = 1";

$result = mysqli_query($conn, $query);

$menuBagItems = isset($_SESSION['Cust_Id']) ? mcd_get_customer_bag_items($conn, (int) $_SESSION['Cust_Id']) : mcd_get_guest_bag_items();
$menuBagTotal = 0;

foreach ($menuBagItems as $menuBagItem) {
    $menuBagTotal += isset($menuBagItem['total']) ? (float) $menuBagItem['total'] : ((float) $menuBagItem['price']) * ((int) $menuBagItem['quantity']);
}
?>


<div class="sub-nav-wrapper">
    <div class="sub-nav-container">
        <nav class="sub-nav">
            <a href="menu.php?category=Featured" <?php echo ($nav_cat == 'Featured') ? 'class="active"' : '' ?>>Featured</a>
            <a href="menu.php?category=Group Meals" <?php echo ($nav_cat == 'Group Meals') ? 'class="active"' : '' ?>>Group Meals</a>
            <a href="menu.php?category=Chicken" <?php echo ($nav_cat == 'Chicken') ? 'class="active"' : '' ?>>Chicken</a>
            <a href="menu.php?category=Burgers" <?php echo ($nav_cat == 'Burgers') ? 'class="active"' : '' ?>>Burgers</a>
            <a href="menu.php?category=McSpaghetti" <?php echo ($nav_cat == 'McSpaghetti') ? 'class="active"' : '' ?>>McSpaghetti</a>
            <a href="menu.php?category=Desserts%20%26%20Drinks" <?php echo ($nav_cat == 'Desserts & Drinks') ? 'class="active"' : '' ?>>Desserts & Drinks</a>
            <a href="menu.php?category=McCafe" <?php echo ($nav_cat == 'McCafe') ? 'class="active"' : '' ?>>McCafe</a>  
            <a href="menu.php?category=Fries%20%26%20Extras" <?php echo ($nav_cat == 'Fries & Extras') ? 'class="active"' : '' ?>>Fries & Extras</a>                                     
            <a href="menu.php?category=Happy Meal" <?php echo ($nav_cat == 'Happy Meal') ? 'class="active"' : '' ?>>Happy Meal</a>
            <a href="menu.php?category=Sulit Busog Meals" <?php echo ($nav_cat == 'Sulit Busog Meals') ? 'class="active"' : '' ?>>Sulit Busog Meals</a>
        </nav>
    </div>
</div>

<div class="menu-page-wrapper">
    
    <div class="menu-content-container">
        <main class="product-section">
            <div class="menu-grid">
    <?php 
    if ($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            ?>
            <a class="card-link" href="productdetails.php?id=<?php echo $row['Menu_MenuItemId']; ?>">
                <div class="card">
                    <div class="card-image">
                        <img src="uploads/<?php echo htmlspecialchars($row['Menu_ImageURL']); ?>" alt="<?php echo htmlspecialchars($row['Menu_Name']); ?>">
                    </div>
                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($row['Menu_Name']); ?></h3>
                        <p class="Menu_Price">₱<?php echo number_format($row['Menu_Price'], 2); ?></p>
                        <span class="order-btn">View Details</span>
                    </div>
                </div>
            </a>
            <?php
        }
    } else {
        echo "<p>No items found for " . htmlspecialchars($nav_cat) . ".</p>";
    }
    ?>                     
</div>
        </main>
    </div>
</div>

<?php include('includes/footer.php'); ?>
