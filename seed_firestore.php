<?php
/**
 * Seed Firestore with sample data for McDelivery Web
 *
 * Usage: C:\xampp\php\php.exe seed_firestore.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;
use Kreait\Firebase\Factory;

$firebaseCredentialsPath = __DIR__ . '/config/firebase-credentials.json';

if (!file_exists($firebaseCredentialsPath)) {
    die("Missing: config/firebase-credentials.json\n");
}

echo "=== McDelivery Firestore Seeder ===\n\n";

// Initialize Firebase
echo "Initializing Firebase... ";
try {
    $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
    $credentials = new ServiceAccountCredentials($scopes, $firebaseCredentialsPath);

    $factory = (new Factory)
        ->withServiceAccount($firebaseCredentialsPath)
        ->withFirestoreClientConfig([
            'transport' => 'rest',
            'credentials' => $credentials,
        ]);

    $firestore = $factory->createFirestore();
    $db = $firestore->database();
    echo "OK\n";
} catch (Exception $e) {
    die("FAILED: " . $e->getMessage() . "\n");
}

// ==================== BRANCHES ====================
echo "\n--- Branches ---\n";
$branches = [
    [
        'Brnch_Id' => '1',
        'Brnch_Name' => 'McDonald\'s McDozen Mall Main',
        'Brnch_Street' => '120 Rizal Avenue',
        'Brnch_Barangay' => 'Barangay 1',
        'Brnch_City' => 'Manila',
        'Brnch_Municipality' => 'Manila',
        'Brnch_PostalCode' => '1000',
        'Brnch_Phone' => '0281234567',
    ],
    [
        'Brnch_Id' => '2',
        'Brnch_Name' => 'McDonald\'s Makati Central',
        'Brnch_Street' => '456 Ayala Avenue',
        'Brnch_Barangay' => 'San Lorenzo',
        'Brnch_City' => 'Makati',
        'Brnch_Municipality' => 'Makati',
        'Brnch_PostalCode' => '1223',
        'Brnch_Phone' => '0287654321',
    ],
    [
        'Brnch_Id' => '3',
        'Brnch_Name' => 'McDonald\'s Quezon City North',
        'Brnch_Street' => '789 Commonwealth Avenue',
        'Brnch_Barangay' => 'Diliman',
        'Brnch_City' => 'Quezon City',
        'Brnch_Municipality' => 'Quezon City',
        'Brnch_PostalCode' => '1101',
        'Brnch_Phone' => '0298765432',
    ],
];

foreach ($branches as $branch) {
    $id = $branch['Brnch_Id'];
    $db->collection('branches')->document($id)->set($branch);
    echo "  Created branch: {$branch['Brnch_Name']}\n";
}

// ==================== MENU ITEMS ====================
echo "\n--- Menu Items ---\n";
$menuItems = [
    // Burgers
    ['Menu_MenuItemId' => '1', 'Menu_Name' => 'Big Mac', 'Menu_Description' => 'Two all-beef patties, special sauce, lettuce, cheese, pickles, onions on a sesame seed bun', 'Menu_Price' => 185.00, 'Menu_Category' => 'Burger', 'Menu_ImageURL' => 'bigmac.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '2', 'Menu_Name' => 'Quarter Pounder with Cheese', 'Menu_Description' => 'Quarter pound beef patty with melted cheese, pickles, onions, ketchup, and mustard', 'Menu_Price' => 195.00, 'Menu_Category' => 'Burger', 'Menu_ImageURL' => 'quarter_pounder.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '3', 'Menu_Name' => 'McChicken Sandwich', 'Menu_Description' => 'Crispy chicken patty with lettuce and mayonnaise on a toasted bun', 'Menu_Price' => 165.00, 'Menu_Category' => 'Burger', 'Menu_ImageURL' => 'mcchicken.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '4', 'Menu_Name' => 'Double Cheeseburger', 'Menu_Description' => 'Two beef patties with melted cheese, pickles, onions, ketchup, and mustard', 'Menu_Price' => 145.00, 'Menu_Category' => 'Burger', 'Menu_ImageURL' => 'double_cheeseburger.jpg', 'Menu_Available' => true],
    // Chicken
    ['Menu_MenuItemId' => '5', 'Menu_Name' => 'Chicken McDo 1pc with Rice', 'Menu_Description' => 'One piece of crispy juicy chicken with steamed rice', 'Menu_Price' => 125.00, 'Menu_Category' => 'Chicken', 'Menu_ImageURL' => 'chicken_1pc.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '6', 'Menu_Name' => 'Chicken McDo 2pc with Rice', 'Menu_Description' => 'Two pieces of crispy juicy chicken with steamed rice', 'Menu_Price' => 185.00, 'Menu_Category' => 'Chicken', 'Menu_ImageURL' => 'chicken_2pc.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '7', 'Menu_Name' => 'Chicken McNuggets 6pc', 'Menu_Description' => 'Six pieces of tender chicken McNuggets with your choice of dip', 'Menu_Price' => 135.00, 'Menu_Category' => 'Chicken', 'Menu_ImageURL' => 'nuggets_6pc.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '8', 'Menu_Name' => 'Chicken McNuggets 10pc', 'Menu_Description' => 'Ten pieces of tender chicken McNuggets with your choice of dip', 'Menu_Price' => 195.00, 'Menu_Category' => 'Chicken', 'Menu_ImageURL' => 'nuggets_10pc.jpg', 'Menu_Available' => true],
    // Fries & Sides
    ['Menu_MenuItemId' => '9', 'Menu_Name' => 'French Fries (Medium)', 'Menu_Description' => 'Golden crispy french fries cooked to perfection', 'Menu_Price' => 75.00, 'Menu_Category' => 'Fries & Sides', 'Menu_ImageURL' => 'fries_medium.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '10', 'Menu_Name' => 'French Fries (Large)', 'Menu_Description' => 'Large serving of golden crispy french fries', 'Menu_Price' => 95.00, 'Menu_Category' => 'Fries & Sides', 'Menu_ImageURL' => 'fries_large.jpg', 'Menu_Available' => true],
    // Drinks
    ['Menu_MenuItemId' => '11', 'Menu_Name' => 'Coca-Cola (Medium)', 'Menu_Description' => 'Refreshing ice-cold Coca-Cola', 'Menu_Price' => 55.00, 'Menu_Category' => 'Drinks', 'Menu_ImageURL' => 'coke_medium.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '12', 'Menu_Name' => 'Coca-Cola (Large)', 'Menu_Description' => 'Large ice-cold Coca-Cola', 'Menu_Price' => 75.00, 'Menu_Category' => 'Drinks', 'Menu_ImageURL' => 'coke_large.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '13', 'Menu_Name' => 'Iced Coffee', 'Menu_Description' => 'Refreshing iced coffee drink', 'Menu_Price' => 65.00, 'Menu_Category' => 'Drinks', 'Menu_ImageURL' => 'iced_coffee.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '14', 'Menu_Name' => 'Vanilla Milkshake', 'Menu_Description' => 'Creamy vanilla milkshake', 'Menu_Price' => 85.00, 'Menu_Category' => 'Drinks', 'Menu_ImageURL' => 'vanilla_shake.jpg', 'Menu_Available' => true],
    // Desserts
    ['Menu_MenuItemId' => '15', 'Menu_Name' => 'McFlurry with Oreo', 'Menu_Description' => 'Creamy vanilla soft serve mixed with Oreo cookie pieces', 'Menu_Price' => 75.00, 'Menu_Category' => 'Desserts', 'Menu_ImageURL' => 'mcflurry_oreo.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '16', 'Menu_Name' => 'Apple Pie', 'Menu_Description' => 'Warm crispy apple pie with cinnamon filling', 'Menu_Price' => 45.00, 'Menu_Category' => 'Desserts', 'Menu_ImageURL' => 'apple_pie.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '17', 'Menu_Name' => 'Sundae (Chocolate)', 'Menu_Description' => 'Creamy vanilla soft serve with chocolate syrup', 'Menu_Price' => 55.00, 'Menu_Category' => 'Desserts', 'Menu_ImageURL' => 'sundae_chocolate.jpg', 'Menu_Available' => true],
    // Breakfast
    ['Menu_MenuItemId' => '18', 'Menu_Name' => 'Egg McMuffin', 'Menu_Description' => 'Freshly cracked egg, Canadian bacon, and melted cheese on a toasted English muffin', 'Menu_Price' => 115.00, 'Menu_Category' => 'Breakfast', 'Menu_ImageURL' => 'egg_mcmuffin.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '19', 'Menu_Name' => 'Hotcakes with Sausage', 'Menu_Description' => 'Fluffy hotcakes with butter and syrup, served with sausage patty', 'Menu_Price' => 145.00, 'Menu_Category' => 'Breakfast', 'Menu_ImageURL' => 'hotcakes_sausage.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '20', 'Menu_Name' => 'Sausage McMuffin with Egg', 'Menu_Description' => 'Sausage patty, freshly cracked egg, and melted cheese on a toasted English muffin', 'Menu_Price' => 125.00, 'Menu_Category' => 'Breakfast', 'Menu_ImageURL' => 'sausage_mcmuffin.jpg', 'Menu_Available' => true],
    // Exclusives (also listed under Chicken for the "Chicken" category filter)
    ['Menu_MenuItemId' => '21', 'Menu_Name' => 'McSpaghetti', 'Menu_Description' => 'Savory spaghetti with meaty sauce and grated cheese', 'Menu_Price' => 105.00, 'Menu_Category' => 'Exclusives', 'Menu_ImageURL' => 'mcspaghetti.jpg', 'Menu_Available' => true],
    ['Menu_MenuItemId' => '22', 'Menu_Name' => 'Burger McDo', 'Menu_Description' => 'Classic beef burger with ketchup, mustard, and pickles', 'Menu_Price' => 85.00, 'Menu_Category' => 'Exclusives', 'Menu_ImageURL' => 'burger_mcdo.jpg', 'Menu_Available' => true],
];

foreach ($menuItems as $item) {
    $id = $item['Menu_MenuItemId'];
    $db->collection('menuItems')->document($id)->set($item);
    echo "  Created menu item: {$item['Menu_Name']} ({$item['Menu_Category']}) - ₱{$item['Menu_Price']}\n";
}

// ==================== COUPONS ====================
echo "\n--- Coupons ---\n";
$now = new DateTime();
$coupons = [
    [
        'Coupn_Code' => 'FREESHIP50',
        'Coupn_Description' => 'Free delivery on orders above ₱500',
        'Coupn_DiscountValue' => 50.00,
        'Coupn_MinOrderAmount' => 500.00,
        'Coupn_MaxDiscount' => 50.00,
        'Coupn_ExpiryDate' => (clone $now)->modify('+30 days')->format('Y-m-d\TH:i:s\Z'),
        'Coupn_IsActive' => true,
    ],
    [
        'Coupn_Code' => 'BURGER10',
        'Coupn_Description' => '₱10 off any burger item',
        'Coupn_DiscountValue' => 10.00,
        'Coupn_MinOrderAmount' => 100.00,
        'Coupn_MaxDiscount' => 10.00,
        'Coupn_ExpiryDate' => (clone $now)->modify('+60 days')->format('Y-m-d\TH:i:s\Z'),
        'Coupn_IsActive' => true,
    ],
    [
        'Coupn_Code' => 'WELCOME20',
        'Coupn_Description' => '₱20 off your first order',
        'Coupn_DiscountValue' => 20.00,
        'Coupn_MinOrderAmount' => 200.00,
        'Coupn_MaxDiscount' => 20.00,
        'Coupn_ExpiryDate' => (clone $now)->modify('+90 days')->format('Y-m-d\TH:i:s\Z'),
        'Coupn_IsActive' => true,
    ],
];

foreach ($coupons as $i => $coupon) {
    $id = (string)($i + 1);
    $docData = $coupon;
    $docData['Coupn_Id'] = $id;
    $db->collection('coupons')->document($id)->set($docData);
    echo "  Created coupon: {$coupon['Coupn_Code']}\n";
}

// ==================== BRANCH STATS ====================
echo "\n--- Branch Stats ---\n";
foreach ($branches as $branch) {
    $id = $branch['Brnch_Id'];
    $db->collection('branchStats')->document($id)->set([
        'totalOrders' => 0,
        'revenue' => 0.0,
        'pendingOrders' => 0,
        'preparingOrders' => 0,
        'readyOrders' => 0,
        'completedOrders' => 0,
        'lastUpdated' => $now->format('Y-m-d\TH:i:s\Z'),
    ]);
    echo "  Created stats for branch: {$branch['Brnch_Name']}\n";
}

echo "\n=== Seeding Complete! ===\n";
echo "Populated:\n";
echo "  - " . count($branches) . " branches\n";
echo "  - " . count($menuItems) . " menu items\n";
echo "  - " . count($coupons) . " coupons\n";
echo "  - " . count($branches) . " branch stats\n";
echo "\nYou can now use the app at http://localhost/mcdelivery-web/\n";
