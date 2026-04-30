<?php 
// 1. Set the timezone first!
date_default_timezone_set('Asia/Manila');
include('config/db.php'); 
include('includes/header.php'); 

// 2. Logic to determine Greeting and Category
$hour = date('H'); // Gets hour from 00 to 23

if ($hour >= 5 && $hour < 11) {
    $greeting = "Good Morning!";
    $subtext = "Say hooray with these breakfast treats!";
    $category = "Breakfast";
} else if ($hour >= 11 && $hour < 17) {
    $greeting = "Good Afternoon!";
    $subtext = "Enjoy your favorite lunch meals!";
    $category = "Dinner Specials"; 
} else {
    $greeting = "Good Evening!";
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
                <div class="card">
                    <div class="card-image">
                        <img src="<?php echo $row['Menu_ImageURL']; ?>" alt="Product">
                    </div>
                    <div class="card-info">
                        <h3><?php echo $row['Menu_Name']; ?></h3>
                        <p class="Menu_Price">₱<?php echo number_format($row['Menu_Price'], 2); ?></p>
                    </div>
                </div>
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
                    <a href="category.php?id=featured" class="cat-item">
                        <img src="images/menu/featured.jpg" alt="Featured">
                        <span>Featured</span>
                    </a>
                    <a href="category.php?id=group" class="cat-item">
                        <img src="images/menu/groupmeals.png" alt="Group Meals">
                        <span>Group Meals</span>
                    </a>
                    <a href="category.php?id=chicken" class="cat-item">
                        <img src="images/menu/chickenfish.png" alt="Chicken & Fish">
                        <span>Chicken & Fish</span>
                    </a>
                    <a href="category.php?id=burgers" class="cat-item">
                        <img src="images/menu/burgers.png" alt="Burgers">
                        <span>Burgers</span>
                    </a>
                    <a href="category.php?id=spaghetti" class="cat-item">
                        <img src="images/menu/spag.png" alt="McSpaghetti">
                        <span>McSpaghetti</span>
                    </a>
                    <a href="category.php?id=ricebowl" class="cat-item">
                        <img src="images/menu/ricebowl.png" alt="Rice Bowl">
                        <span>Rice Bowl</span>
                    </a>
                    <a href="category.php?id=dessertsdrinks" class="cat-item">
                        <img src="images/menu/dessertsdrinks.png" alt="Desserts & Drinks">
                        <span>Desserts & Drinks</span>
                    </a>
                    <a href="category.php?id=mccafe" class="cat-item">
                        <img src="images/menu/mccafe.png" alt="Mc Cafe">
                        <span>Mc Cafe</span>
                    </a>
                    <a href="category.php?id=friesextra" class="cat-item">
                        <img src="images/menu/friesextra.png" alt="Fries">
                        <span>Fries</span>
                    </a>
                    <a href="category.php?id=happymeals" class="cat-item">
                        <img src="images/menu/happymeal.png" alt="Happy Meal">
                        <span>Happy Meal</span>
                    </a>
                    <a href="category.php?id=sulitmeals" class="cat-item">
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
            <div class="card">
                <div class="card-image">
                    <img src="<?php echo $row['Menu_ImageURL']; ?>" alt="Exclusive Product">
                </div>
                <div class="card-info">
                    <h3><?php echo $row['Menu_Name']; ?></h3>
                    <p class="Menu_Price">₱<?php echo number_format($row['Menu_Price'], 2); ?></p>
                </div>
            </div>
            <?php
        }
    } else { echo "<p>No exclusive items found.</p>"; }
    ?>
    </div>
</div>
</main>

<?php include('includes/footer.php'); ?>
<script src="js/slider.js?v=<?php echo time(); ?>"></script>