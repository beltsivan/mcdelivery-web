<?php 
// 1. Set the timezone first!
date_default_timezone_set('Asia/Manila');
include('config/db.php'); 
include('includes/header.php'); 

// 2. Logic to determine Greeting and Category
$hour = date('H'); // Gets hour from 00 to 23

$fname = isset($_SESSION['Cust_FName']) ? $_SESSION['Cust_FName'] : "Guest";

if ($hour >= 5 && $hour < 11) {
    $greeting = "Good Morning, " . $fname . "!";
    $subtext = "Say hooray with these breakfast treats!";
    $category = "Breakfast";
} else if ($hour >= 11 && $hour < 17) {
    $greeting = "Good Afternoon, " . $fname . "!";
    $subtext = "Enjoy your favorite lunch meals!";
    $category = "Dinner Specials";
} else {
    $greeting = "Good Evening, " . $fname . "!";
    $subtext = "Treat yourself with these dinner specials!";
    $category = "Dinner Specials";
}
?>

<main>
    <div class="main-container">
        <section class="slider-container">
            <div class="slider">
                <img src="images/cearealbanner.jpg" class="slide active" alt="Promo 1">
                <img src="images/goldenbanner.jpg" class="slide" alt="Promo 2">
                <img src="images/mariobanner.jpg" class="slide" alt="Promo 3">
                <img src="images/sulitbanner.jpg" class="slide" alt="Promo 4">
            </div>
        </section>

        <div class="content-section">
            <h1><?php echo $greeting; ?></h1>
            <p><?php echo $subtext; ?></p>
        </div>

        <div class="product-grid">
        <?php
        // 3. Fetch ONLY products matching the current time category
        // Escaping $category for security
        $safe_category = mysqli_real_escape_string($conn, $category);
        $query = "SELECT * FROM McdoMenuItem WHERE Menu_Category = '$safe_category' AND Menu_Available = 1";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                ?>
                <a href="productdetails.php?id=<?php echo $row['Menu_MenuItemId']; ?>">
                    <div class="card">
                    <div class="card-image">
                        <img src="<?php echo $row['Menu_ImageURL']; ?>" alt="Product">
                    </div>
                    <div class="card-info">
                        <h3><?php echo $row['Menu_Name']; ?></h3>
                        <p class="Menu_Price">₱<?php echo number_format($row['Menu_Price'], 2); ?></p>
                    </div>
                </div>
                
                </a>
                
                <?php
            } 
        } else {
            echo "<p>No $category items available right now.</p>";
        }
        ?>
        </div>
    </div>
    <div class="category-section main-container">
    <h2>Menu</h2>
    <p class="subtext">What are you craving for today?</p>
    <div class="category-wrapper">
        <div class="scroll-container">
            <button class="scroll-btn left" onclick="scrollGrid(-1)">&#10094;</button>
                <div class="category-grid" id="categoryGrid">
                    <a href="menu.php?category=Featured" class="cat-item">
                        <img src="images/menu/featured.jpg" alt="Featured">
                        <span>Featured</span>
                    </a>
                    <a href="menu.php?category=Group Meals" class="cat-item">
                        <img src="images/menu/groupmeals.png" alt="Group Meals">
                        <span>Group Meals</span>
                    </a>
                    <a href="menu.php?category=Chicken" class="cat-item">
                        <img src="images/menu/chickenfish.png" alt="Chicken & Fish">
                        <span>Chicken & Fish</span>
                    </a>
                    <a href="menu.php?category=Burgers" class="cat-item">
                        <img src="images/menu/burgers.png" alt="Burgers">
                        <span>Burgers</span>
                    </a>
                    <a href="menu.php?category=McSpaghetti" class="cat-item">
                        <img src="images/menu/spag.png" alt="McSpaghetti">
                        <span>McSpaghetti</span>
                    </a>
                    <a href="menu.php?category=Rice Bowl" class="cat-item">
                        <img src="images/menu/ricebowl.png" alt="Rice Bowl">
                        <span>Rice Bowl</span>
                    </a>
                    <a href="menu.php?category=Desserts%20%26%20Drinks" class="cat-item">
                        <img src="images/menu/dessertsdrinks.png" alt="Desserts & Drinks">
                        <span>Desserts & Drinks</span>
                    </a>
                    <a href="menu.php?category=McCafe" class="cat-item">
                        <img src="images/menu/mccafe.png" alt="Mc Cafe">
                        <span>Mc Cafe</span>
                    </a>
                    <a href="menu.php?category=Fries%20%26%20Extras" class="cat-item">
                        <img src="images/menu/friesextra.png" alt="Fries">
                        <span>Fries</span>
                    </a>
                    <a href="menu.php?category=Happy Meal" class="cat-item">
                        <img src="images/menu/happymeal.png" alt="Happy Meal">
                        <span>Happy Meal</span>
                    </a>
                    <a href="menu.php?category=Sulit Busog Meals" class="cat-item">
                        <img src="images/menu/sulitmeals.png" alt="Sulit Meals">
                        <span>Sulit-Busog Meals</span>
                    </a>
                </div>
            <button class="scroll-btn right" onclick="scrollGrid(1)">&#10095;</button>
        </div>
    </div>
    
    
</div>


<div class="exclusives-section main-container">
    <h2>McDelivery Exclusives</h2>
    <p class="subtext">Enjoy these special offers only with McDelivery!</p>
    
    <div class="product-grid">
    <?php
    // Querying specifically for 'Exclusives' category
    $exclusive_query = "SELECT * FROM McdoMenuItem WHERE Menu_Category = 'Exclusives' AND Menu_Available = 1";
    $exclusive_result = mysqli_query($conn, $exclusive_query);

    if (mysqli_num_rows($exclusive_result) > 0) {
        while($row = mysqli_fetch_assoc($exclusive_result)) {
            // Reusing your exact same card structure again!
            ?>
        <a href="productdetails.php?id=<?php echo $row['Menu_MenuItemId']; ?>">
            <div class="card">
                <div class="card-image">
                    <img src="<?php echo $row['Menu_ImageURL']; ?>" alt="Exclusive Product">
                </div>
                <div class="card-info">
                    <h3><?php echo $row['Menu_Name']; ?></h3>
                    <p class="Menu_Price">₱<?php echo number_format($row['Menu_Price'], 2); ?></p>
                </div>
            </div>
        </a>
            
            <?php
        }
    } else { echo "<p>No exclusive items found.</p>"; }
    ?>
    </div>
</div>

<div class="featured-section main-container">
    <h2>Featured</h2>
    <p class="subtext">Discover your new favorites here!</p>
    
    <div class="product-grid">
    <?php
// Added 'LIMIT 4' to the end of the query
$featured_query = "SELECT * FROM McdoMenuItem 
                   WHERE Menu_Category = 'Featured' 
                   AND Menu_Available = 1 
                   LIMIT 5"; 
$featured_result = mysqli_query($conn, $featured_query);

if (mysqli_num_rows($featured_result) > 0) {
    while($row = mysqli_fetch_assoc($featured_result)) {
        ?>
        <a href="productdetails.php?id=<?php echo $row['Menu_MenuItemId']; ?>">
            <div class="card">
            <div class="card-image">
                <img src="<?php echo $row['Menu_ImageURL']; ?>" alt="Featured Item">
            </div>
            <div class="card-info">
                <h3><?php echo $row['Menu_Name']; ?></h3>
                <p class="Menu_Price">₱<?php echo number_format($row['Menu_Price'], 2); ?></p>
            </div>
        </div>
        </a>
        
        <?php
    }
}
?>
    </div>
</div>
<script src="js/slider.js?v=<?php echo time(); ?>"></script>
</main>

<?php include('includes/footer.php'); ?>
