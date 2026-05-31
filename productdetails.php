<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

include('config/db.php');
require_once('includes/cart.php');

$product = null;
$productId = filter_input(INPUT_GET, 'id');

function findProductById($productId) {
    global $firestore, $firebaseInitialized;
    if (!$firebaseInitialized) return null;

    $db = $firestore->database();
    $doc = $db->collection('menuItems')->document((string) $productId)->snapshot();

    if (!$doc->exists()) return null;

    $data = $doc->data();
    $data['Menu_MenuItemId'] = $doc->id();
    return $data;
}

if ($productId) {
    $product = findProductById($productId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_bag']) && $product) {
    unset($_SESSION['bag']);

    if (isset($_SESSION['Cust_Id'])) {
        mcd_add_customer_bag_item(null, $_SESSION['Cust_Id'], $product);
    } else {
        $_SESSION['guest_bag_flash'] = [
            'id' => (int) $product['Menu_MenuItemId'],
            'name' => $product['Menu_Name'],
            'price' => (float) $product['Menu_Price'],
            'image' => $product['Menu_ImageURL'],
            'quantity' => 1,
            'total' => (float) $product['Menu_Price'],
        ];
    }

    header('Location: productdetails.php?id=' . $product['Menu_MenuItemId'] . '&bag=1');
    exit;
}

$imagePath = '';

if ($product) {
    $imagePath = mcd_normalize_image_path($product['Menu_ImageURL']);
}

include('includes/header.php');
?>

<main class="product-details-page">
    <div class="main-container">
        <?php if ($product): ?>
            <a class="back-link" href="menu.php">&larr; Back to Menu</a>

            <section class="product-details-card">
                <div class="product-details-image">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['Menu_Name']); ?>">
                </div>

                <div class="product-details-info">
                    <p class="product-category"><?php echo htmlspecialchars($product['Menu_Category']); ?></p>
                    <h1><?php echo htmlspecialchars($product['Menu_Name']); ?></h1>

                    <?php if (!empty($product['Menu_Description'])): ?>
                        <p class="product-description"><?php echo htmlspecialchars($product['Menu_Description']); ?></p>
                    <?php endif; ?>

                    <p class="product-price">₱<?php echo number_format($product['Menu_Price'], 2); ?></p>
                    <form method="POST" action="productdetails.php?id=<?php echo $product['Menu_MenuItemId']; ?>">
                        <button class="add-to-cart" type="submit" name="add_to_bag">Add to Bag</button>
                    </form>
                </div>
            </section>
        <?php else: ?>
            <section class="product-not-found">
                <h1>Product not found</h1>
                <p>The item you selected is unavailable or does not exist.</p>
                <a class="back-link" href="menu.php">Back to Menu</a>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include('includes/footer.php'); ?>
