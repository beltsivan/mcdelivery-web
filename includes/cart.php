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
?>
