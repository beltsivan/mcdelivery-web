<?php
function mcd_normalize_image_path($imagePath) {
    $imagePath = trim((string) $imagePath);

    if ($imagePath !== '' && !preg_match('/^(https?:)?\/\//i', $imagePath) && strpos($imagePath, '/') === false) {
        $imagePath = 'uploads/' . $imagePath;
    }

    return $imagePath;
}

function mcd_get_customer_bag_count($conn, $custId) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(Cart_Quantity), 0) AS item_count FROM cartitem WHERE Cart_Cust_Id = ?");

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "i", $custId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return isset($row['item_count']) ? (int) $row['item_count'] : 0;
}

function mcd_get_customer_bag_items($conn, $custId) {
    $items = [];
    $sql = "SELECT c.Cart_Menu_MenuItemId, c.Cart_Quantity, c.Cart_ItemPrice, c.Cart_Total, m.Menu_Name, m.Menu_ImageURL
            FROM cartitem c
            INNER JOIN mcdomenuitem m ON m.Menu_MenuItemId = c.Cart_Menu_MenuItemId
            WHERE c.Cart_Cust_Id = ?
            ORDER BY c.Cart_Id ASC";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return $items;
    }

    mysqli_stmt_bind_param($stmt, "i", $custId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => (int) $row['Cart_Menu_MenuItemId'],
            'name' => $row['Menu_Name'],
            'price' => (float) $row['Cart_ItemPrice'],
            'image' => $row['Menu_ImageURL'],
            'quantity' => (int) $row['Cart_Quantity'],
            'total' => (float) $row['Cart_Total'],
        ];
    }

    mysqli_stmt_close($stmt);

    return $items;
}

function mcd_add_customer_bag_item($conn, $custId, $product) {
    $menuItemId = (int) $product['Menu_MenuItemId'];
    $price = (float) $product['Menu_Price'];
    $stmt = mysqli_prepare($conn, "SELECT Cart_Id, Cart_Quantity FROM cartitem WHERE Cart_Cust_Id = ? AND Cart_Menu_MenuItemId = ? LIMIT 1");

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $custId, $menuItemId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existingItem = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($existingItem) {
        $cartId = (int) $existingItem['Cart_Id'];
        $quantity = ((int) $existingItem['Cart_Quantity']) + 1;
        $total = $price * $quantity;
        $updateStmt = mysqli_prepare($conn, "UPDATE cartitem SET Cart_Quantity = ?, Cart_ItemPrice = ?, Cart_Total = ? WHERE Cart_Id = ? AND Cart_Cust_Id = ?");

        if (!$updateStmt) {
            return false;
        }

        mysqli_stmt_bind_param($updateStmt, "iddii", $quantity, $price, $total, $cartId, $custId);
        $success = mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);

        return $success;
    }

    $quantity = 1;
    $total = $price;
    $insertStmt = mysqli_prepare($conn, "INSERT INTO cartitem (Cart_Cust_Id, Cart_Menu_MenuItemId, Cart_Quantity, Cart_ItemPrice, Cart_Total) VALUES (?, ?, ?, ?, ?)");

    if (!$insertStmt) {
        return false;
    }

    mysqli_stmt_bind_param($insertStmt, "iiidd", $custId, $menuItemId, $quantity, $price, $total);
    $success = mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    return $success;
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

function mcd_update_cart_quantity($conn, $custId, $menuItemId, $action) {
    $menuItemId = (int) $menuItemId;
    $stmt = mysqli_prepare($conn, "SELECT Cart_Id, Cart_Quantity FROM cartitem WHERE Cart_Cust_Id = ? AND Cart_Menu_MenuItemId = ? LIMIT 1");

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $custId, $menuItemId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existingItem = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$existingItem) {
        return false;
    }

    $cartId = (int) $existingItem['Cart_Id'];
    $currentQty = (int) $existingItem['Cart_Quantity'];

    if ($action === 'increase') {
        $newQty = $currentQty + 1;
    } elseif ($action === 'decrease') {
        $newQty = $currentQty - 1;
    } else {
        return false;
    }

    if ($newQty <= 0) {
        $deleteStmt = mysqli_prepare($conn, "DELETE FROM cartitem WHERE Cart_Id = ? AND Cart_Cust_Id = ?");
        mysqli_stmt_bind_param($deleteStmt, "ii", $cartId, $custId);
        $success = mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);
        return $success;
    }

    $stmtPrice = mysqli_prepare($conn, "SELECT Menu_Price FROM mcdomenuitem WHERE Menu_MenuItemId = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtPrice, "i", $menuItemId);
    mysqli_stmt_execute($stmtPrice);
    $priceResult = mysqli_stmt_get_result($stmtPrice);
    $priceRow = mysqli_fetch_assoc($priceResult);
    mysqli_stmt_close($stmtPrice);

    $price = $priceRow ? (float) $priceRow['Menu_Price'] : 0;
    $newTotal = $price * $newQty;

    $updateStmt = mysqli_prepare($conn, "UPDATE cartitem SET Cart_Quantity = ?, Cart_ItemPrice = ?, Cart_Total = ? WHERE Cart_Id = ? AND Cart_Cust_Id = ?");
    mysqli_stmt_bind_param($updateStmt, "iddii", $newQty, $price, $newTotal, $cartId, $custId);
    $success = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    return $success;
}

function mcd_get_payment_info($conn, $orderId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM payment WHERE Pay_Order_Id = ? LIMIT 1");

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $payment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $payment;
}

function mcd_checkout($conn, $custId, $addressId, $paymentMethod = 'Cash on Delivery', $branchId = null) {
    $cartItems = mcd_get_customer_bag_items($conn, $custId);

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

    $stmt = mysqli_prepare($conn, "INSERT INTO mcorder (Order_Cust_Id, Order_OrderDate, Order_Status, Order_TotalAmount, Order_Quantity, Order_DeliveryFee, Order_Add_Id, Order_Brnch_Id) VALUES (?, NOW(), 'Pending', ?, ?, ?, ?, ?)");

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "iddiii", $custId, $grandTotal, $totalQuantity, $deliveryFee, $addressId, $branchId);
    $orderCreated = mysqli_stmt_execute($stmt);
    $orderId = mysqli_stmt_insert_id($stmt);
    mysqli_stmt_close($stmt);

    if (!$orderCreated) {
        return false;
    }

    $payStatus = ($paymentMethod === 'Cash on Delivery') ? 'Pending' : 'Done';

    $payStmt = mysqli_prepare($conn, "INSERT INTO payment (Pay_Order_Id, Pay_PaymentType, Pay_PaymentStatus, Pay_PaidAmount) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($payStmt, "issd", $orderId, $paymentMethod, $payStatus, $grandTotal);
    mysqli_stmt_execute($payStmt);
    mysqli_stmt_close($payStmt);

    $dlvryStmt = mysqli_prepare($conn, "INSERT INTO mcdeliverystatus (Dlvry_Order_Id, Dlvry_StatusUpdate) VALUES (?, 'Order Placed')");
    mysqli_stmt_bind_param($dlvryStmt, "i", $orderId);
    mysqli_stmt_execute($dlvryStmt);
    mysqli_stmt_close($dlvryStmt);

    $itemStmt = mysqli_prepare($conn, "INSERT INTO orderitem (OrderItem_Order_Id, OrderItem_MenuItemId, OrderItem_Quantity, OrderItem_Price, OrderItem_Total) VALUES (?, ?, ?, ?, ?)");

    if ($itemStmt) {
        foreach ($cartItems as $item) {
            mysqli_stmt_bind_param($itemStmt, "iiidd", $orderId, $item['id'], $item['quantity'], $item['price'], $item['total']);
            mysqli_stmt_execute($itemStmt);
        }
        mysqli_stmt_close($itemStmt);
    }

    $clearStmt = mysqli_prepare($conn, "DELETE FROM cartitem WHERE Cart_Cust_Id = ?");
    mysqli_stmt_bind_param($clearStmt, "i", $custId);
    mysqli_stmt_execute($clearStmt);
    mysqli_stmt_close($clearStmt);

    return $orderId;
}

function mcd_get_order_items($conn, $orderId) {
    $items = [];
    $stmt = mysqli_prepare($conn, "SELECT oi.*, m.Menu_Name, m.Menu_ImageURL FROM orderitem oi INNER JOIN mcdomenuitem m ON m.Menu_MenuItemId = oi.OrderItem_MenuItemId WHERE oi.OrderItem_Order_Id = ?");

    if (!$stmt) {
        return $items;
    }

    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    mysqli_stmt_close($stmt);

    return $items;
}

function mcd_get_kitchen_orders($conn, $statusFilter = null, $branchId = null) {
    $orders = [];
    $sql = "SELECT o.*, c.Cust_FName, c.Cust_LName, a.Add_Street, a.Add_Barangay, a.Add_City, a.Add_Municipality, a.Add_PostalCode FROM mcorder o INNER JOIN customer c ON c.Cust_Id = o.Order_Cust_Id LEFT JOIN address a ON a.Add_Id = o.Order_Add_Id";

    $conditions = [];
    $params = [];
    $types = '';

    if ($statusFilter) {
        if (is_array($statusFilter)) {
            $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
            $conditions[] = "o.Order_Status IN ($placeholders)";
            foreach ($statusFilter as $sf) {
                $params[] = $sf;
                $types .= 's';
            }
        } else {
            $conditions[] = "o.Order_Status = ?";
            $params[] = $statusFilter;
            $types .= 's';
        }
    }

    if ($branchId !== null) {
        $conditions[] = "o.Order_Brnch_Id = ?";
        $params[] = $branchId;
        $types .= 'i';
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY o.Order_OrderDate DESC";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return $orders;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $row['items'] = mcd_get_order_items($conn, $row['Order_Id']);
        $orders[] = $row;
    }

    mysqli_stmt_close($stmt);

    return $orders;
}

function mcd_accept_order($conn, $orderId, $prepTime) {
    $stmt = mysqli_prepare($conn, "UPDATE mcorder SET Order_Status = 'Preparing', Order_PrepTime = ? WHERE Order_Id = ? AND Order_Status = 'Pending'");

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $prepTime, $orderId);
    $success = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($success && $affected > 0) {
        $dlvryStmt = mysqli_prepare($conn, "INSERT INTO mcdeliverystatus (Dlvry_Order_Id, Dlvry_StatusUpdate) VALUES (?, 'Preparing')");
        mysqli_stmt_bind_param($dlvryStmt, "i", $orderId);
        mysqli_stmt_execute($dlvryStmt);
        mysqli_stmt_close($dlvryStmt);

        return true;
    }

    return false;
}

function mcd_update_order_status($conn, $orderId, $newStatus) {
    $stmt = mysqli_prepare($conn, "UPDATE mcorder SET Order_Status = ? WHERE Order_Id = ?");

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "si", $newStatus, $orderId);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($success) {
        $dlvryStmt = mysqli_prepare($conn, "INSERT INTO mcdeliverystatus (Dlvry_Order_Id, Dlvry_StatusUpdate) VALUES (?, ?)");
        mysqli_stmt_bind_param($dlvryStmt, "is", $orderId, $newStatus);
        mysqli_stmt_execute($dlvryStmt);
        mysqli_stmt_close($dlvryStmt);

        if ($newStatus === 'Completed') {
            $payStmt = mysqli_prepare($conn, "UPDATE payment SET Pay_PaymentStatus = 'Done' WHERE Pay_Order_Id = ? AND Pay_PaymentType = 'Cash on Delivery'");
            mysqli_stmt_bind_param($payStmt, "i", $orderId);
            mysqli_stmt_execute($payStmt);
            mysqli_stmt_close($payStmt);
        }

        return true;
    }

    return false;
}

function mcd_get_customer_orders($conn, $custId) {
    $orders = [];
    $stmt = mysqli_prepare($conn, "SELECT o.*, b.Brnch_Name, b.Brnch_Street, b.Brnch_Barangay, b.Brnch_City, b.Brnch_Municipality, b.Brnch_PostalCode, (SELECT Dlvry_StatusUpdate FROM mcdeliverystatus WHERE Dlvry_Order_Id = o.Order_Id ORDER BY Dlvry_DateTime DESC LIMIT 1) AS LatestStatus FROM mcorder o LEFT JOIN mcbranch b ON b.Brnch_Id = o.Order_Brnch_Id WHERE o.Order_Cust_Id = ? ORDER BY o.Order_OrderDate DESC");

    if (!$stmt) {
        return $orders;
    }

    mysqli_stmt_bind_param($stmt, "i", $custId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $row['items'] = mcd_get_order_items($conn, $row['Order_Id']);
        $orders[] = $row;
    }

    mysqli_stmt_close($stmt);

    return $orders;
}
?>
