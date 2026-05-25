<?php
/**
 * MySQL → Firestore Migration Script
 *
 * Usage: php migrate_to_firestore.php
 * 
 * Prerequisites:
 * 1. MySQL database 'mcd_db' must be running with existing data
 * 2. Firebase project must be set up with service account JSON in config/
 * 3. Run: composer require kreait/firebase-php
 * 4. Have curl extension enabled for Firebase Auth REST API
 */

// Config
$mysqlHost = 'localhost';
$mysqlUser = 'root';
$mysqlPass = '';
$mysqlDb = 'mcd_db';
$firebaseApiKey = 'AIzaSyAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'; // REPLACE WITH YOUR KEY

// ------------------ SETUP ------------------
echo "=== McDelivery MySQL → Firestore Migration ===\n\n";

// Connect to MySQL
echo "Connecting to MySQL... ";
$mysqli = new mysqli($mysqlHost, $mysqlUser, $mysqlPass, $mysqlDb);
if ($mysqli->connect_error) {
    die("FAILED: " . $mysqli->connect_error . "\n");
}
echo "OK\n";

// Connect to Firebase
echo "Initializing Firebase... ";
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("FAILED: Run 'composer require kreait/firebase-php' first.\n");
}
require_once __DIR__ . '/vendor/autoload.php';
use Kreait\Firebase\Factory;

$credPath = __DIR__ . '/config/firebase-credentials.json';
if (!file_exists($credPath)) {
    die("FAILED: Place firebase-credentials.json in config/ directory.\n");
}

$factory = (new Factory)->withServiceAccount($credPath);
$firestore = $factory->createFirestore();
$auth = $factory->createAuth();
$db = $firestore->database();
echo "OK\n";

// ------------------ ID MAPPING ------------------
// Old MySQL INT ID → New Firebase string ID
$customerIdMap = []; // old Cust_Id → Firebase UID
$menuItemIdMap = []; // old Menu_MenuItemId → Firestore doc ID
$branchIdMap = [];   // old Brnch_Id → Firestore doc ID
$addressIdMap = [];  // old Add_Id → Firestore doc ID

// Helper to call Firebase Auth REST
function firebaseSignIn($email, $password) {
    global $firebaseApiKey;
    $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=$firebaseApiKey";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $email,
        'password' => $password,
        'returnSecureToken' => true,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$httpCode, json_decode($response, true)];
}

// ------------------ 1. MIGRATE BRANCHES ------------------
echo "\n--- Migrating Branches ---\n";
$result = $mysqli->query("SELECT * FROM McBranch");
$branchCount = 0;
while ($row = $result->fetch_assoc()) {
    $oldId = $row['Brnch_Id'];
    $branchId = (string) $oldId; // Use old ID as document ID
    
    $db->collection('branches')->document($branchId)->set([
        'Brnch_Name' => $row['Brnch_Name'] ?? '',
        'Brnch_Street' => $row['Brnch_Street'] ?? '',
        'Brnch_Barangay' => $row['Brnch_Barangay'] ?? '',
        'Brnch_City' => $row['Brnch_City'] ?? '',
        'Brnch_Municipality' => $row['Brnch_Municipality'] ?? '',
        'Brnch_PostalCode' => $row['Brnch_PostalCode'] ?? '',
        'Brnch_Phone' => $row['Brnch_Phone'] ?? '',
    ]);
    
    $branchIdMap[$oldId] = $branchId;
    $branchCount++;
    echo "  Branch #$oldId → {$row['Brnch_Name']}\n";
}
echo "  Total: $branchCount branches migrated.\n";

// ------------------ 2. MIGRATE MENU ITEMS ------------------
echo "\n--- Migrating Menu Items ---\n";
$result = $mysqli->query("SELECT * FROM McdoMenuItem");
$menuCount = 0;
while ($row = $result->fetch_assoc()) {
    $oldId = $row['Menu_MenuItemId'];
    $menuId = (string) $oldId;
    
    $db->collection('menuItems')->document($menuId)->set([
        'Menu_Name' => $row['Menu_Name'] ?? '',
        'Menu_Description' => $row['Menu_Description'] ?? '',
        'Menu_Price' => (float) ($row['Menu_Price'] ?? 0),
        'Menu_Category' => $row['Menu_Category'] ?? '',
        'Menu_ImageURL' => $row['Menu_ImageURL'] ?? '',
        'Menu_Available' => (bool) ($row['Menu_Available'] ?? true),
    ]);
    
    $menuItemIdMap[$oldId] = $menuId;
    $menuCount++;
    if ($menuCount % 20 === 0) echo "  ...$menuCount items migrated\n";
}
echo "  Total: $menuCount menu items migrated.\n";

// ------------------ 3. MIGRATE CUSTOMERS ------------------
echo "\n--- Migrating Customers ---\n";
$result = $mysqli->query("SELECT * FROM Customer");
$custCount = 0;
$skipCount = 0;

while ($row = $result->fetch_assoc()) {
    $oldId = (int) $row['Cust_Id'];
    $email = $row['Cust_Email'];
    $password = $row['Cust_Password']; // bcrypt hash
    
    // Check if already created
    if (isset($customerIdMap[$oldId])) {
        $skipCount++;
        continue;
    }
    
    try {
        // Create Firebase Auth user (import with existing bcrypt hash)
        $user = $auth->createUser([
            'email' => $email,
            'password' => 'TemporaryPass123!', // Will be overwritten by import
            'displayName' => ($row['Cust_FName'] ?? '') . ' ' . ($row['Cust_LName'] ?? ''),
        ]);
        $uid = $user->uid;
        
        // Create Firestore doc
        $db->collection('customers')->document($uid)->set([
            'Cust_Id' => $uid,
            'Cust_FName' => $row['Cust_FName'] ?? '',
            'Cust_LName' => $row['Cust_LName'] ?? '',
            'Cust_Email' => $email,
            'Cust_Phone' => $row['Cust_Phone'] ?? '',
            'Cust_CreatedAt' => $row['Cust_CreatedAt'] 
                ? new \Google\Cloud\Core\Timestamp(new \DateTime($row['Cust_CreatedAt'])) 
                : new \Google\Cloud\Core\Timestamp(new \DateTime()),
        ]);
        
        $customerIdMap[$oldId] = $uid;
        $custCount++;
        
        if ($custCount % 50 === 0) echo "  ...$custCount customers migrated\n";
    } catch (\Exception $e) {
        echo "  SKIP: Customer #$oldId ($email) - {$e->getMessage()}\n";
        $skipCount++;
    }
}
echo "  Total: $custCount customers migrated, $skipCount skipped.\n";

// ------------------ 5. MIGRATE STAFF ------------------
echo "\n--- Migrating Staff ---\n";
$result = $mysqli->query("SELECT * FROM Staff");
$staffCount = 0;
$staffIdMap = [];

while ($row = $result->fetch_assoc()) {
    $oldId = (int) $row['Staff_Id'];
    $email = $row['Staff_Email'];
    
    if (empty($email)) {
        echo "  SKIP: Staff #$oldId — no email\n";
        continue;
    }
    
    try {
        // Create or check existing Firebase Auth user
        try {
            $existingUser = $auth->getUserByEmail($email);
            $uid = $existingUser->uid;
            echo "  EXISTS: Staff #$oldId ($email) — linking\n";
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            $user = $auth->createUser([
                'email' => $email,
                'password' => $row['Staff_Password'] ?: 'default123',
                'displayName' => ($row['Staff_FName'] ?? '') . ' ' . ($row['Staff_LName'] ?? ''),
            ]);
            $uid = $user->uid;
        }
        
        // Create staff doc
        $docData = [
            'Staff_Id' => $uid,
            'Staff_Role' => $row['Staff_Role'] ?? 'Staff',
            'Staff_FName' => $row['Staff_FName'] ?? '',
            'Staff_LName' => $row['Staff_LName'] ?? '',
            'Staff_Phone' => $row['Staff_Phone'] ?? '',
            'Staff_Email' => $email,
        ];
        
        if (!empty($row['Staff_Brnch_Id']) && isset($branchIdMap[(int) $row['Staff_Brnch_Id']])) {
            $docData['Staff_Brnch_Id'] = $branchIdMap[(int) $row['Staff_Brnch_Id']];
        }
        
        $db->collection('staff')->document($uid)->set($docData);
        $staffIdMap[$oldId] = $uid;
        $staffCount++;
    } catch (\Exception $e) {
        echo "  SKIP: Staff #$oldId ($email) - {$e->getMessage()}\n";
    }
}
echo "  Total: $staffCount staff migrated.\n";

// ------------------ 6. MIGRATE ADDRESSES ------------------
echo "\n--- Migrating Addresses ---\n";
$result = $mysqli->query("SELECT * FROM Address");
$addrCount = 0;

while ($row = $result->fetch_assoc()) {
    $oldId = (int) $row['Add_Id'];
    $oldCustId = (int) $row['Add_Cust_Id'];
    $firebaseUid = $customerIdMap[$oldCustId] ?? null;
    
    if (!$firebaseUid) {
        echo "  SKIP: Address #$oldId — customer #$oldCustId not migrated\n";
        continue;
    }
    
    $addrId = (string) $oldId;
    $db->collection('addresses')->document($addrId)->set([
        'Add_Cust_Id' => $firebaseUid,
        'Add_Street' => $row['Add_Street'] ?? '',
        'Add_Barangay' => $row['Add_Barangay'] ?? '',
        'Add_City' => $row['Add_City'] ?? '',
        'Add_Municipality' => $row['Add_Municipality'] ?? '',
        'Add_PostalCode' => $row['Add_PostalCode'] ?? '',
    ]);
    
    $addressIdMap[$oldId] = $addrId;
    $addrCount++;
}
echo "  Total: $addrCount addresses migrated.\n";

// ------------------ 7. MIGRATE CART ITEMS ------------------
echo "\n--- Migrating Cart Items ---\n";
$result = $mysqli->query("SELECT c.*, m.Menu_Name, m.Menu_ImageURL 
    FROM CartItem c 
    INNER JOIN McdoMenuItem m ON m.Menu_MenuItemId = c.Cart_Menu_MenuItemId");
$cartCount = 0;

while ($row = $result->fetch_assoc()) {
    $oldCustId = (int) $row['Cart_Cust_Id'];
    $firebaseUid = $customerIdMap[$oldCustId] ?? null;
    
    if (!$firebaseUid) {
        continue; // Skip cart items for unmigrated customers
    }
    
    $db->collection('customers')
        ->document($firebaseUid)
        ->collection('cartItems')
        ->add([
            'Cart_Cust_Id' => $firebaseUid,
            'Cart_Menu_MenuItemId' => (string) $row['Cart_Menu_MenuItemId'],
            'Cart_Quantity' => (int) ($row['Cart_Quantity'] ?? 0),
            'Cart_ItemPrice' => (float) ($row['Cart_ItemPrice'] ?? 0),
            'Cart_Total' => (float) ($row['Cart_Total'] ?? 0),
            'Menu_Name' => $row['Menu_Name'] ?? '',
            'Menu_ImageURL' => $row['Menu_ImageURL'] ?? '',
        ]);
    
    $cartCount++;
}
echo "  Total: $cartCount cart items migrated.\n";

// ------------------ 8. MIGRATE ORDERS ------------------
echo "\n--- Migrating Orders ---\n";
$result = $mysqli->query("SELECT * FROM McOrder");
$orderCount = 0;

while ($order = $result->fetch_assoc()) {
    $oldOrderId = (int) $order['Order_Id'];
    $oldCustId = (int) $order['Order_Cust_Id'];
    $firebaseUid = $customerIdMap[$oldCustId] ?? null;
    
    if (!$firebaseUid) {
        echo "  SKIP: Order #$oldOrderId — customer #$oldCustId not migrated\n";
        continue;
    }
    
    $orderId = (string) $oldOrderId;
    
    // Build embedded items
    $itemsResult = $mysqli->query("SELECT oi.*, m.Menu_Name, m.Menu_ImageURL 
        FROM orderitem oi 
        INNER JOIN McdoMenuItem m ON m.Menu_MenuItemId = oi.OrderItem_MenuItemId 
        WHERE oi.OrderItem_Order_Id = $oldOrderId");
    
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = [
            'OrderItem_MenuItemId' => (string) $item['OrderItem_MenuItemId'],
            'Menu_Name' => $item['Menu_Name'] ?? '',
            'Menu_ImageURL' => $item['Menu_ImageURL'] ?? '',
            'OrderItem_Quantity' => (int) ($item['OrderItem_Quantity'] ?? 0),
            'OrderItem_Price' => (float) ($item['OrderItem_Price'] ?? 0),
            'OrderItem_Total' => (float) ($item['OrderItem_Total'] ?? 0),
        ];
    }
    
    // Build embedded payment
    $payResult = $mysqli->query("SELECT * FROM Payment WHERE Pay_Order_Id = $oldOrderId LIMIT 1");
    $paymentRow = $payResult->fetch_assoc();
    $payment = $paymentRow ? [
        'Pay_PaymentType' => $paymentRow['Pay_PaymentType'] ?? 'Cash on Delivery',
        'Pay_PaymentStatus' => $paymentRow['Pay_PaymentStatus'] ?? 'Pending',
        'Pay_PaidAmount' => (float) ($paymentRow['Pay_PaidAmount'] ?? 0),
        'Pay_TransactionDate' => $paymentRow['Pay_TransactionDate']
            ? new \Google\Cloud\Core\Timestamp(new \DateTime($paymentRow['Pay_TransactionDate']))
            : new \Google\Cloud\Core\Timestamp(new \DateTime()),
    ] : [
        'Pay_PaymentType' => 'Cash on Delivery',
        'Pay_PaymentStatus' => 'Pending',
        'Pay_PaidAmount' => (float) ($order['Order_TotalAmount'] ?? 0),
        'Pay_TransactionDate' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
    ];
    
    // Build embedded delivery status
    $dsResult = $mysqli->query("SELECT * FROM McDeliveryStatus WHERE Dlvry_Order_Id = $oldOrderId ORDER BY Dlvry_DateTime ASC");
    $deliveryStatus = [];
    while ($ds = $dsResult->fetch_assoc()) {
        $deliveryStatus[] = [
            'Dlvry_StatusUpdate' => $ds['Dlvry_StatusUpdate'] ?? '',
            'Dlvry_DateTime' => $ds['Dlvry_DateTime']
                ? new \Google\Cloud\Core\Timestamp(new \DateTime($ds['Dlvry_DateTime']))
                : new \Google\Cloud\Core\Timestamp(new \DateTime()),
        ];
    }
    
    if (empty($deliveryStatus)) {
        $deliveryStatus[] = [
            'Dlvry_StatusUpdate' => 'Order Placed',
            'Dlvry_DateTime' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
        ];
    }
    
    // Get address info
    $oldAddrId = (int) ($order['Order_Add_Id'] ?? 0);
    $addrDoc = $oldAddrId && isset($addressIdMap[$oldAddrId])
        ? $db->collection('addresses')->document($addressIdMap[$oldAddrId])->snapshot()
        : null;
    $addressInfo = ($addrDoc && $addrDoc->exists()) ? $addrDoc->data() : [];
    
    // Get branch info
    $branchId = null;
    $branchInfo = [];
    if (!empty($order['Order_Brnch_Id']) && isset($branchIdMap[(int) $order['Order_Brnch_Id']])) {
        $branchId = $branchIdMap[(int) $order['Order_Brnch_Id']];
        $bDoc = $db->collection('branches')->document($branchId)->snapshot();
        if ($bDoc->exists()) $branchInfo = $bDoc->data();
    }
    
    // Get customer name
    $custDoc = $db->collection('customers')->document($firebaseUid)->snapshot();
    $custData = $custDoc->exists() ? $custDoc->data() : [];
    
    $orderData = [
        'Order_Cust_Id' => $firebaseUid,
        'Order_Add_Id' => $addressIdMap[$oldAddrId] ?? null,
        'Order_Brnch_Id' => $branchId,
        'Order_OrderDate' => $order['Order_OrderDate']
            ? new \Google\Cloud\Core\Timestamp(new \DateTime($order['Order_OrderDate']))
            : new \Google\Cloud\Core\Timestamp(new \DateTime()),
        'Order_Status' => $order['Order_Status'] ?? 'Pending',
        'Order_TotalAmount' => (float) ($order['Order_TotalAmount'] ?? 0),
        'Order_Quantity' => (int) ($order['Order_Quantity'] ?? 0),
        'Order_DeliveryFee' => (float) ($order['Order_DeliveryFee'] ?? 49),
        'Order_PrepTime' => (int) ($order['Order_PrepTime'] ?? 0),
        'Cust_FName' => $custData['Cust_FName'] ?? '',
        'Cust_LName' => $custData['Cust_LName'] ?? '',
        'Add_Street' => $addressInfo['Add_Street'] ?? '',
        'Add_Barangay' => $addressInfo['Add_Barangay'] ?? '',
        'Add_City' => $addressInfo['Add_City'] ?? '',
        'Add_Municipality' => $addressInfo['Add_Municipality'] ?? '',
        'Add_PostalCode' => $addressInfo['Add_PostalCode'] ?? '',
        'Brnch_Name' => $branchInfo['Brnch_Name'] ?? '',
        'Brnch_Street' => $branchInfo['Brnch_Street'] ?? '',
        'Brnch_City' => $branchInfo['Brnch_City'] ?? '',
        'items' => $items,
        'payment' => $payment,
        'deliveryStatus' => $deliveryStatus,
    ];
    
    $db->collection('orders')->document($orderId)->set($orderData);
    $orderCount++;
    
    if ($orderCount % 50 === 0) echo "  ...$orderCount orders migrated\n";
}
echo "  Total: $orderCount orders migrated.\n";

// ------------------ DONE ------------------
echo "\n=== Migration Complete ===\n";
echo "Summary:\n";
echo "  Branches:  $branchCount\n";
echo "  Menu Items: $menuCount\n";
echo "  Customers: $custCount\n";
echo "  Staff:     $staffCount\n";
echo "  Addresses: $addrCount\n";
echo "  Cart Items: $cartCount\n";
echo "  Orders:    $orderCount\n";
echo "\n";

// Save ID mapping to JSON for reference
$mapping = [
    'customers' => $customerIdMap,
    'menuItems' => $menuItemIdMap,
    'branches' => $branchIdMap,
    'addresses' => $addressIdMap,
];
file_put_contents(__DIR__ . '/migration_id_map.json', json_encode($mapping, JSON_PRETTY_PRINT));
echo "ID mapping saved to: migration_id_map.json\n";

// IMPORTANT NOTE about staff passwords
echo "\n⚠️  IMPORTANT: Staff passwords were NOT migrated.\n";
echo "   Staff must use 'Forgot Password' flow or an Admin must reset them.\n";
echo "   Update the \$firebaseApiKey in config/db.php with your Web API Key.\n";
echo "   Update the \$firebaseApiKey in this script before re-running.\n";

$mysqli->close();
echo "\nDone!\n";
