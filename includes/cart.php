<?php
function mcd_normalize_image_path($imagePath) {
    $imagePath = trim((string) $imagePath);

    if ($imagePath !== '' && !preg_match('/^(https?:)?\/\//i', $imagePath) && strpos($imagePath, '/') === false) {
        $imagePath = 'uploads/' . $imagePath;
    }

    return $imagePath;
}

function mcd_get_customer_bag_count($connIgnored, $custId) {
    global $firestore;
    $db = $firestore->database();

    $snapshot = $db->collection('customers')
        ->document($custId)
        ->collection('cartItems')
        ->documents();

    $count = 0;
    foreach ($snapshot as $doc) {
        $data = $doc->data();
        $count += (int) ($data['Cart_Quantity'] ?? 0);
    }

    return $count;
}

function mcd_get_customer_bag_items($connIgnored, $custId) {
    global $firestore;
    $db = $firestore->database();
    $items = [];

    $snapshot = $db->collection('customers')
        ->document($custId)
        ->collection('cartItems')
        ->documents();

    foreach ($snapshot as $doc) {
        $data = $doc->data();
        $items[] = [
            'id' => (int) ($data['Cart_Menu_MenuItemId'] ?? 0),
            'name' => $data['Menu_Name'] ?? '',
            'price' => (float) ($data['Cart_ItemPrice'] ?? 0),
            'image' => $data['Menu_ImageURL'] ?? '',
            'quantity' => (int) ($data['Cart_Quantity'] ?? 0),
            'total' => (float) ($data['Cart_Total'] ?? 0),
            'cart_item_id' => $doc->id(),
        ];
    }

    return $items;
}

function mcd_add_customer_bag_item($connIgnored, $custId, $product) {
    global $firestore;
    $db = $firestore->database();
    $cartCol = $db->collection('customers')->document($custId)->collection('cartItems');

    $menuItemId = (int) $product['Menu_MenuItemId'];
    $price = (float) $product['Menu_Price'];

    // Check if item already in cart by iterating all items
    $snapshot = $cartCol->documents();
    $existingDoc = null;
    foreach ($snapshot as $doc) {
        if ($doc->exists() && (int) ($doc->data()['Cart_Menu_MenuItemId'] ?? 0) === $menuItemId) {
            $existingDoc = $doc;
            break;
        }
    }

    if ($existingDoc) {
        $data = $existingDoc->data();
        $quantity = ((int) ($data['Cart_Quantity'] ?? 0)) + 1;
        $total = $price * $quantity;
        $existingDoc->reference()->set([
            'Cart_Quantity' => $quantity,
            'Cart_ItemPrice' => $price,
            'Cart_Total' => $total,
        ], ['merge' => true]);
        return true;
    }

    // Add new item
    $cartCol->add([
        'Cart_Cust_Id' => $custId,
        'Cart_Menu_MenuItemId' => (string) $menuItemId,
        'Cart_Quantity' => 1,
        'Cart_ItemPrice' => $price,
        'Cart_Total' => $price,
        'Menu_Name' => $product['Menu_Name'] ?? '',
        'Menu_ImageURL' => $product['Menu_ImageURL'] ?? '',
    ]);

    return true;
}

function mcd_get_guest_bag_items() {
    if (empty($_SESSION['guest_bag_flash'])) {
        return [];
    }

    return [$_SESSION['guest_bag_flash']];
}

function mcd_get_guest_bag_count() {
    if (empty($_SESSION['guest_bag_flash'])) {
        return 0;
    }

    return (int) $_SESSION['guest_bag_flash']['quantity'];
}

function mcd_update_cart_quantity($connIgnored, $custId, $cartItemId, $action) {
    global $firestore;
    $db = $firestore->database();
    $docRef = $db->collection('customers')->document($custId)->collection('cartItems')->document($cartItemId);

    $doc = $docRef->snapshot();
    if (!$doc->exists()) {
        return false;
    }

    $data = $doc->data();
    $currentQty = (int) ($data['Cart_Quantity'] ?? 0);

    if ($action === 'increase') {
        $newQty = $currentQty + 1;
    } elseif ($action === 'decrease') {
        $newQty = $currentQty - 1;
    } else {
        return false;
    }

    if ($newQty <= 0) {
        $docRef->delete();
        return true;
    }

    $docRef->set([
        'Cart_Quantity' => $newQty,
        'Cart_ItemPrice' => $data['Cart_ItemPrice'] ?? 0,
        'Cart_Total' => ($data['Cart_ItemPrice'] ?? 0) * $newQty,
    ], ['merge' => true]);

    return true;
}

function mcd_get_payment_info($connIgnored, $orderId) {
    global $firestore;
    $db = $firestore->database();

    $doc = $db->collection('orders')->document((string) $orderId)->snapshot();

    if (!$doc->exists()) {
        return null;
    }

    $data = $doc->data();
    $payment = $data['payment'] ?? [];

    return [
        'Pay_Id' => $orderId . '_payment',
        'Pay_Order_Id' => (string) $orderId,
        'Pay_PaymentType' => $payment['Pay_PaymentType'] ?? '',
        'Pay_PaymentStatus' => $payment['Pay_PaymentStatus'] ?? '',
        'Pay_PaidAmount' => $payment['Pay_PaidAmount'] ?? 0,
        'Pay_TransactionDate' => $payment['Pay_TransactionDate'] ?? null,
    ];
}

function mcd_checkout($connIgnored, $custId, $addressId, $paymentMethod = 'Cash on Delivery', $branchId = null) {
    global $firestore, $firebaseInitialized;
    if (!$firebaseInitialized) return false;

    $db = $firestore->database();

    $cartItems = mcd_get_customer_bag_items(null, $custId);

    if (empty($cartItems)) {
        return false;
    }

    $totalQuantity = 0;
    $subtotal = 0;

    foreach ($cartItems as $item) {
        $totalQuantity += (int) $item['quantity'];
        $subtotal += (float) $item['total'];
    }

    $deliveryFee = 49;
    $grandTotal = $subtotal + $deliveryFee;

    // Get branch info for denormalization
    $branchInfo = [];
    if ($branchId) {
        $branchDoc = $db->collection('branches')->document((string) $branchId)->snapshot();
        if ($branchDoc->exists()) {
            $branchInfo = $branchDoc->data();
        }
    }

    // Get customer info for denormalization
    $customerDoc = $db->collection('customers')->document($custId)->snapshot();
    $customerData = $customerDoc->exists() ? $customerDoc->data() : [];

    // Get address info for denormalization
    $addressInfo = [];
    if ($addressId) {
        $addressDoc = $db->collection('addresses')->document((string) $addressId)->snapshot();
        if ($addressDoc->exists()) {
            $addressInfo = $addressDoc->data();
        }
    }

    $payStatus = ($paymentMethod === 'Cash on Delivery') ? 'Pending' : 'Done';

    $now = new \Google\Cloud\Core\Timestamp(new \DateTime());

    // Build the order document with embedded items, payment, deliveryStatus
    $orderData = [
        'Order_Cust_Id' => $custId,
        'Order_Add_Id' => (string) $addressId,
        'Order_Brnch_Id' => $branchId ? (string) $branchId : null,
        'Order_OrderDate' => $now,
        'Order_Status' => 'Pending',
        'Order_TotalAmount' => $grandTotal,
        'Order_Quantity' => $totalQuantity,
        'Order_DeliveryFee' => $deliveryFee,
        'Order_PrepTime' => 0,
        // Denormalized customer info
        'Cust_FName' => $customerData['Cust_FName'] ?? '',
        'Cust_LName' => $customerData['Cust_LName'] ?? '',
        // Denormalized address info
        'Add_Street' => $addressInfo['Add_Street'] ?? '',
        'Add_Barangay' => $addressInfo['Add_Barangay'] ?? '',
        'Add_City' => $addressInfo['Add_City'] ?? '',
        'Add_Municipality' => $addressInfo['Add_Municipality'] ?? '',
        'Add_PostalCode' => $addressInfo['Add_PostalCode'] ?? '',
        // Denormalized branch info
        'Brnch_Name' => $branchInfo['Brnch_Name'] ?? '',
        'Brnch_Street' => $branchInfo['Brnch_Street'] ?? '',
        'Brnch_City' => $branchInfo['Brnch_City'] ?? '',
        // Embedded items array
        'items' => [],
        // Embedded payment object
        'payment' => [
            'Pay_PaymentType' => $paymentMethod,
            'Pay_PaymentStatus' => $payStatus,
            'Pay_PaidAmount' => $grandTotal,
            'Pay_TransactionDate' => $now,
        ],
        // Embedded delivery status array
        'deliveryStatus' => [
            [
                'Dlvry_StatusUpdate' => 'Order Placed',
                'Dlvry_DateTime' => $now,
            ],
        ],
    ];

    // Build embedded items
    foreach ($cartItems as $item) {
        $orderData['items'][] = [
            'OrderItem_MenuItemId' => (string) $item['id'],
            'Menu_Name' => $item['name'],
            'Menu_ImageURL' => $item['image'],
            'OrderItem_Quantity' => $item['quantity'],
            'OrderItem_Price' => $item['price'],
            'OrderItem_Total' => $item['total'],
        ];
    }

    try {
        // Create the order document directly (not batch) to avoid batch/REST issues
        $orderRef = $db->collection('orders')->newDocument();
        $orderRef->set($orderData);

        // Delete cart items individually
        $cartCol = $db->collection('customers')->document($custId)->collection('cartItems');
        $cartSnapshot = $cartCol->documents();
        foreach ($cartSnapshot as $cartDoc) {
            if ($cartDoc->exists()) {
                $cartDoc->reference()->delete();
            }
        }
    } catch (\Exception $e) {
        error_log('Checkout error: ' . $e->getMessage());
        return false;
    }

    return $orderRef->id();
}

function mcd_get_order_items($connIgnored, $orderId) {
    global $firestore;
    $db = $firestore->database();
    $items = [];

    $doc = $db->collection('orders')->document((string) $orderId)->snapshot();

    if (!$doc->exists()) {
        return $items;
    }

    $data = $doc->data();
    $embeddedItems = $data['items'] ?? [];

    foreach ($embeddedItems as $idx => $item) {
        $items[] = [
            'OrderItem_Id' => $orderId . '_item_' . $idx,
            'OrderItem_Order_Id' => (string) $orderId,
            'OrderItem_MenuItemId' => $item['OrderItem_MenuItemId'] ?? '',
            'Menu_Name' => $item['Menu_Name'] ?? '',
            'Menu_ImageURL' => $item['Menu_ImageURL'] ?? '',
            'OrderItem_Quantity' => $item['OrderItem_Quantity'] ?? 0,
            'OrderItem_Price' => $item['OrderItem_Price'] ?? 0,
            'OrderItem_Total' => $item['OrderItem_Total'] ?? 0,
        ];
    }

    return $items;
}

function mcd_get_kitchen_orders($connIgnored, $statusFilter = null, $branchId = null) {
    global $firestore;
    $db = $firestore->database();
    $orders = [];

    $query = $db->collection('orders');

    if ($statusFilter) {
        if (is_array($statusFilter)) {
            $query = $query->where('Order_Status', 'in', $statusFilter);
        } else {
            $query = $query->where('Order_Status', '=', $statusFilter);
        }
    }

    if ($branchId !== null) {
        $query = $query->where('Order_Brnch_Id', '=', (string) $branchId);
    }

    $snapshot = $query->documents();

    foreach ($snapshot as $doc) {
        if (!$doc->exists()) continue;
        $row = $doc->data();
        $row['Order_Id'] = $doc->id();

        // Items are already embedded — just reference them
        $embeddedItems = $row['items'] ?? [];
        $row['items'] = [];
        foreach ($embeddedItems as $idx => $item) {
            $row['items'][] = [
                'OrderItem_Id' => $doc->id() . '_item_' . $idx,
                'OrderItem_Order_Id' => $doc->id(),
                'OrderItem_MenuItemId' => $item['OrderItem_MenuItemId'] ?? '',
                'Menu_Name' => $item['Menu_Name'] ?? '',
                'Menu_ImageURL' => $item['Menu_ImageURL'] ?? '',
                'OrderItem_Quantity' => $item['OrderItem_Quantity'] ?? 0,
                'OrderItem_Price' => $item['OrderItem_Price'] ?? 0,
                'OrderItem_Total' => $item['OrderItem_Total'] ?? 0,
            ];
        }

        $orders[] = $row;
    }

    usort($orders, function ($a, $b) {
        $aTime = isset($a['Order_OrderDate']) ? (is_string($a['Order_OrderDate']) ? strtotime($a['Order_OrderDate']) : $a['Order_OrderDate']->get()->format('U')) : 0;
        $bTime = isset($b['Order_OrderDate']) ? (is_string($b['Order_OrderDate']) ? strtotime($b['Order_OrderDate']) : $b['Order_OrderDate']->get()->format('U')) : 0;
        return $bTime - $aTime;
    });

    return $orders;
}

function mcd_accept_order($connIgnored, $orderId, $prepTime) {
    global $firestore;
    $db = $firestore->database();
    $docRef = $db->collection('orders')->document((string) $orderId);

    $doc = $docRef->snapshot();
    if (!$doc->exists() || ($doc->data()['Order_Status'] ?? '') !== 'Pending') {
        return false;
    }

    $now = new \Google\Cloud\Core\Timestamp(new \DateTime());

    $docRef->update([
        ['path' => 'Order_Status', 'value' => 'Preparing'],
        ['path' => 'Order_PrepTime', 'value' => $prepTime],
    ]);

    // Add status to deliveryStatus array
    $docRef->update([
        ['path' => 'deliveryStatus', 'value' => array_merge(
            $doc->data()['deliveryStatus'] ?? [],
            [['Dlvry_StatusUpdate' => 'Preparing', 'Dlvry_DateTime' => $now]]
        )],
    ]);

    return true;
}

function mcd_update_order_status($connIgnored, $orderId, $newStatus) {
    global $firestore;
    $db = $firestore->database();
    $docRef = $db->collection('orders')->document((string) $orderId);

    $doc = $docRef->snapshot();
    if (!$doc->exists()) {
        return false;
    }

    $now = new \Google\Cloud\Core\Timestamp(new \DateTime());
    $data = $doc->data();

    $docRef->update([
        ['path' => 'Order_Status', 'value' => $newStatus],
    ]);

    // Add to deliveryStatus array
    $deliveryStatus = $data['deliveryStatus'] ?? [];
    $deliveryStatus[] = ['Dlvry_StatusUpdate' => $newStatus, 'Dlvry_DateTime' => $now];
    $docRef->update([
        ['path' => 'deliveryStatus', 'value' => $deliveryStatus],
    ]);

    // If completed, update payment status for COD
    if ($newStatus === 'Completed') {
        $payment = $data['payment'] ?? [];
        if (($payment['Pay_PaymentType'] ?? '') === 'Cash on Delivery') {
            $payment['Pay_PaymentStatus'] = 'Done';
            $docRef->update([
                ['path' => 'payment', 'value' => $payment],
            ]);
        }
    }

    return true;
}

function mcd_get_customer_orders($connIgnored, $custId) {
    global $firestore;
    $db = $firestore->database();
    $orders = [];

    $snapshot = $db->collection('orders')
        ->where('Order_Cust_Id', '=', $custId)
        ->documents();

    foreach ($snapshot as $doc) {
        if (!$doc->exists()) continue;
        $row = $doc->data();
        $row['Order_Id'] = $doc->id();

        // Extract LatestStatus from embedded deliveryStatus array
        $deliveryStatus = $row['deliveryStatus'] ?? [];
        $latestStatus = '';
        if (!empty($deliveryStatus)) {
            $last = end($deliveryStatus);
            $latestStatus = $last['Dlvry_StatusUpdate'] ?? '';
        }
        $row['LatestStatus'] = $latestStatus;

        // Build items array from embedded items
        $embeddedItems = $row['items'] ?? [];
        $row['items'] = [];
        foreach ($embeddedItems as $idx => $item) {
            $row['items'][] = [
                'OrderItem_Id' => $doc->id() . '_item_' . $idx,
                'OrderItem_Order_Id' => $doc->id(),
                'OrderItem_MenuItemId' => $item['OrderItem_MenuItemId'] ?? '',
                'Menu_Name' => $item['Menu_Name'] ?? '',
                'Menu_ImageURL' => $item['Menu_ImageURL'] ?? '',
                'OrderItem_Quantity' => $item['OrderItem_Quantity'] ?? 0,
                'OrderItem_Price' => $item['OrderItem_Price'] ?? 0,
                'OrderItem_Total' => $item['OrderItem_Total'] ?? 0,
            ];
        }

        $orders[] = $row;
    }

    usort($orders, function ($a, $b) {
        $aTime = isset($a['Order_OrderDate']) ? (is_string($a['Order_OrderDate']) ? strtotime($a['Order_OrderDate']) : $a['Order_OrderDate']->get()->format('U')) : 0;
        $bTime = isset($b['Order_OrderDate']) ? (is_string($b['Order_OrderDate']) ? strtotime($b['Order_OrderDate']) : $b['Order_OrderDate']->get()->format('U')) : 0;
        return $bTime - $aTime;
    });

    return $orders;
}
?>
