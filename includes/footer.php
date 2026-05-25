<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/cart.php');

if (!isset($firebaseInitialized)) {
    require_once(__DIR__ . '/../config/db.php');
}

if (isset($_SESSION['Cust_Id'])) {
    $bagItems = mcd_get_customer_bag_items(null, $_SESSION['Cust_Id']);
} else {
    $bagItems = mcd_get_guest_bag_items();
    unset($_SESSION['guest_bag_flash']);
}

$bagTotal = 0;

foreach ($bagItems as $bagItem) {
    $bagTotal += isset($bagItem['total']) ? (float) $bagItem['total'] : ((float) $bagItem['price']) * ((int) $bagItem['quantity']);
}

$deliveryFee = !empty($bagItems) ? 49 : 0;
$grandTotal = $bagTotal + $deliveryFee;
?>
<footer class="main-footer">
    <div class="main-container footer-flex">
        <div class="footer-logo">
            <img src="images/mcdonalds.png" alt="McDonald's Logo">
        </div>

        <div class="footer-links">
            <ul>
                <li><a href="#">About</a></li>
                <li><a href="#">Charity</a></li>
                <li><a href="#">Careers</a></li>
                <li><a href="#">Business Opportunities</a></li>
            </ul>
        </div>

        <div class="footer-links">
            <ul>
                <li><a href="#">Contact Us</a></li>
                <li><a href="#">Terms & Conditions</a></li>
                <li><a href="#">Privacy Policy</a></li>
            </ul>
        </div>

        <div class="footer-social">
            <p>For news and updates, follow us</p>
            <div class="social-icons">
                <a href="#"><img src="images/socials/fb.png" alt="Facebook"></a>
                <a href="#"><img src="images/socials/twitter.png" alt="Twitter"></a>
                <a href="#"><img src="images/socials/instagram.png" alt="Instagram"></a>
            </div>
        </div>
    </div>
</footer>
<div id="sideBag" class="side-bag">
    <div class="bag-header">
        <h3>My Bag</h3>
        <span class="close-bag" onclick="toggleBag()">&times;</span>
    </div>
    <style>
        /* The Sidebar */
.side-bag {
    position: fixed;
    /* Change this to match your header's height */
    top: 80px; 
    
    right: -400px;
    width: 400px;
    
    /* Calculate height: 100% of viewport minus the header height */
    height: calc(100vh - 80px); 
    
    background-color: white;
    z-index: 999; /* Slightly lower than header if header is 1000 */
    box-shadow: -5px 5px 15px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    transition: right 0.3s ease-in-out;
}

/* Slide in state */
.side-bag.open {
    right: 0;
}

/* Header/Footer Styling */
.bag-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
.bag-content { flex: 1; overflow-y: auto; padding: 20px; }
.bag-footer { padding: 20px; background: #fff; border-top: 1px solid #eee; }

.checkout-btn {
    width: 100%;
    background: #ffbc0d; /* McDonald's Yellow */
    border: none;
    padding: 15px;
    border-radius: 10px;
    font-weight: bold;
    cursor: pointer;
}

.bag-item {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.bag-item img {
    width: 70px;
    height: 70px;
    object-fit: contain;
    border-radius: 10px;
    background: #f8f8f8;
}

.item-details {
    flex: 1;
}

.item-details p {
    margin: 0 0 6px;
    font-weight: bold;
    color: #292929;
}

.item-details span {
    color: #555;
    font-size: 14px;
}

.total {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-weight: bold;
}

.total.grand-total {
    border-top: 1px solid #eee;
    margin-top: 12px;
    padding-top: 12px;
}

.empty-bag-message {
    margin-top: 35px;
    color: #777;
    text-align: center;
}

.qty-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 6px 0;
}

.qty-form {
    margin: 0;
}

.qty-btn {
    background: #FFBC0D;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    padding: 0;
}

.qty-btn:hover {
    background: #e5a90b;
}

.qty-value {
    font-weight: bold;
    font-size: 15px;
    min-width: 20px;
    text-align: center;
}

.unit-price {
    color: #999;
    font-size: 12px;
}

.payment-methods {
    border-top: 1px solid #eee;
    padding: 14px 0;
    margin: 8px 0 4px;
}

.pay-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    cursor: pointer;
    font-size: 13px;
    color: #444;
}

.pay-option input[type="radio"] {
    accent-color: #FFBC0D;
    width: 16px;
    height: 16px;
    margin: 0;
}

</style>
    <div class="bag-content">
        <?php if (!empty($bagItems)): ?>
            <?php foreach ($bagItems as $bagItem): ?>
                <?php
                $imagePath = mcd_normalize_image_path($bagItem['image']);
                $lineTotal = isset($bagItem['total']) ? (float) $bagItem['total'] : ((float) $bagItem['price']) * ((int) $bagItem['quantity']);
                ?>
                <div class="bag-item">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($bagItem['name']); ?>">
                    <div class="item-details">
                        <p><?php echo htmlspecialchars($bagItem['name']); ?></p>
                        <div class="qty-controls">
                            <form method="POST" action="update_cart.php" class="qty-form">
                                <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($bagItem['cart_item_id']); ?>">
                                <input type="hidden" name="action" value="decrease">
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                <button type="submit" class="qty-btn">&minus;</button>
                            </form>
                            <span class="qty-value"><?php echo (int) $bagItem['quantity']; ?></span>
                            <form method="POST" action="update_cart.php" class="qty-form">
                                <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($bagItem['cart_item_id']); ?>">
                                <input type="hidden" name="action" value="increase">
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                <button type="submit" class="qty-btn">+</button>
                            </form>
                        </div>
                        <span class="unit-price">₱<?php echo number_format($bagItem['price'], 2); ?> each</span>
                    </div>
                    <strong>₱<?php echo number_format($lineTotal, 2); ?></strong>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-bag-message">Your bag is empty. Add something from the menu.</p>
        <?php endif; ?>
    </div>

    <div class="bag-footer">
        <div class="total">
            <span>Subtotal</span>
            <span>₱<?php echo number_format($bagTotal, 2); ?></span>
        </div>
        <div class="total">
            <span>Delivery fee</span>
            <span>₱<?php echo number_format($deliveryFee, 2); ?></span>
        </div>
        <div class="total grand-total">
            <span>Total</span>
            <span>₱<?php echo number_format($grandTotal, 2); ?></span>
        </div>
        <?php if (isset($_SESSION['Cust_Id'])): ?>
            <form method="POST" action="checkout.php">
                <div class="payment-methods">
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="Cash on Delivery" checked>
                        <span>Cash on Delivery</span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="GCash">
                        <span>GCash</span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="Bank Transfer">
                        <span>Bank Transfer</span>
                    </label>
                </div>
                <button class="checkout-btn" type="submit" <?php echo empty($bagItems) ? 'disabled' : ''; ?>>Proceed to Checkout</button>
            </form>
        <?php else: ?>
            <button class="checkout-btn" onclick="showLogin()" <?php echo empty($bagItems) ? 'disabled' : ''; ?>>Log in to Checkout</button>
        <?php endif; ?>
    </div>
</div>


<script>
function setBagOpen(open) {
    const sideBag = document.getElementById('sideBag');
    const bagOverlay = document.getElementById('bagOverlay');

    if (!sideBag) {
        return;
    }

    sideBag.classList.toggle('open', open);

    if (bagOverlay) {
        bagOverlay.classList.toggle('active', open);
    }
}

function toggleBag() {
    const sideBag = document.getElementById('sideBag');

    if (!sideBag) {
        return;
    }

    setBagOpen(!sideBag.classList.contains('open'));
}

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);

    if (params.get('bag') === '1') {
        setBagOpen(true);
    }
});
</script>
</body>
</html>
