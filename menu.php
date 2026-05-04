<?php include('includes/header.php'); ?>

<div class="sub-nav-wrapper">
    <div class="sub-nav-container">
        <nav class="sub-nav">
            <a href="menu.php?category=Featured" class="active">Featured</a>
            <a href="menu.php?category=Group Meals">Group Meals</a>
            <a href="menu.php?category=Chicken">Chicken</a>
            <a href="menu.php?category=Burgers">Burgers</a>
            <a href="menu.php?category=McSpaghetti">McSpaghetti</a>
            <a href="menu.php?category=Desserts">Desserts</a>
        </nav>
    </div>
</div>

<div class="menu-page-wrapper">
    
    <div class="menu-content-container">
        <main class="product-section">
            <div class="menu-grid">
                <div class="menu-card">
                    <img src="images/products/ala-king.jpg" alt="Ala King">
                    <h3>2-pc. Crispy Chicken FilletAla King Meal</h3>
                    <p class="price">₱152.00</p>
                    <button class="order-btn">Order</button>
                </div>
        </main>
    </div>

    <aside class="cart-sidebar">
        <div class="bag-container">
            <div class="bag-header">
                <div class="delivery-info">
                    <img src="images/icons/rider-icon.png" alt="" style="width:20px;"> 
                    <span>Deliver (Now)</span>
                </div>
                <a href="#" class="change-link">Change</a>
            </div>
            
            <div class="bag-content">
                <h2>My Bag</h2>
                <div class="empty-state">
                    <img src="images/icons/empty-bag.png" alt="Empty Bag">
                    <p>Your bag is empty. Add something from the menu.</p>
                </div>
            </div>
            
            <div class="bag-footer">
                <div class="total-row"><span>Subtotal</span> <span>₱ 0.00</span></div>
                <div class="total-row"><span>Delivery fee</span> <span>₱ 49.00</span></div>
                <div class="total-row grand-total"><span>Total</span> <span>₱ 0.00</span></div>
                <button class="checkout-btn" disabled>Proceed to Checkout</button>
            </div>
        </div>
    </aside>
</div>

<?php include('includes/footer.php'); ?>