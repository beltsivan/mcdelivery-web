# McDelivery Web — Firestore Database Structure

## Overview

This document maps the MySQL schema to Firebase Firestore collections.  
**Key principle:** PHP frontend code remains unchanged — all MySQL-to-Firestore translation happens inside `includes/cart.php`.

---

## Collection Structure

### 1. `customers` → replaces `Customer` table

| Field | Type | Notes |
|-------|------|-------|
| `Cust_Id` | string | = Firebase Auth UID (document ID) |
| `Cust_FName` | string | |
| `Cust_LName` | string | |
| `Cust_Email` | string | |
| `Cust_Phone` | string | |
| `Cust_CreatedAt` | timestamp | |

**Example document:**
```json
{
  "Cust_Id": "abc123DEF...",
  "Cust_FName": "Juan",
  "Cust_LName": "Dela Cruz",
  "Cust_Email": "juan@email.com",
  "Cust_Phone": "09123456789",
  "Cust_CreatedAt": "2025-01-15T10:30:00Z"
}
```

**Note:** Password is NOT stored in Firestore — it is handled by **Firebase Auth**.

---

### 2. `branches` → replaces `McBranch` table

Auto-generated document ID (string).

| Field | Type | MySQL Column |
|-------|------|-------------|
| `Brnch_Id` | string (doc ID) | Brnch_Id (INT → string) |
| `Brnch_Name` | string | Brnch_Name |
| `Brnch_Street` | string | Brnch_Street |
| `Brnch_Barangay` | string | Brnch_Barangay |
| `Brnch_City` | string | Brnch_City |
| `Brnch_Municipality` | string | Brnch_Municipality |
| `Brnch_PostalCode` | string | Brnch_PostalCode |
| `Brnch_Phone` | string | Brnch_Phone |

---

### 3. `staff` → replaces `Staff` table

Document ID = Firebase Auth UID (string).

| Field | Type | MySQL Column |
|-------|------|-------------|
| `Staff_Id` | string (doc ID) | Staff_Id |
| `Staff_Brnch_Id` | string | Staff_Brnch_Id (FK → branches doc ID) |
| `Staff_Role` | string | Staff_Role |
| `Staff_FName` | string | Staff_FName |
| `Staff_LName` | string | Staff_LName |
| `Staff_Phone` | string | Staff_Phone |
| `Staff_Email` | string | Staff_Email |

**Note:** Password is NOT stored — handled by **Firebase Auth**.  
Staff accounts are created via Firebase Admin SDK (Cloud Function or migration script).

---

### 4. `addresses` → replaces `Address` table

Top-level collection (for independent CRUD). Auto-generated document ID.

| Field | Type | MySQL Column |
|-------|------|-------------|
| `Add_Id` | string (doc ID) | Add_Id |
| `Add_Cust_Id` | string | Add_Cust_Id (FK → customers doc ID) |
| `Add_Street` | string | Add_Street |
| `Add_Barangay` | string | Add_Barangay |
| `Add_City` | string | Add_City |
| `Add_Municipality` | string | Add_Municipality |
| `Add_PostalCode` | string | Add_PostalCode |

---

### 5. `menuItems` → replaces `McdoMenuItem` table

Auto-generated document ID.

| Field | Type | MySQL Column |
|-------|------|-------------|
| `Menu_MenuItemId` | string (doc ID) | Menu_MenuItemId |
| `Menu_Name` | string | Menu_Name |
| `Menu_Description` | string | Menu_Description |
| `Menu_Price` | float | Menu_Price (DECIMAL → float) |
| `Menu_Category` | string | Menu_Category |
| `Menu_ImageURL` | string | Menu_ImageURL |
| `Menu_Available` | bool | Menu_Available (BOOLEAN) |

---

### 6. `coupons` → replaces `McdoCoupon` table

Auto-generated document ID. *(Currently unused in code, but modeled.)*

| Field | Type | MySQL Column |
|-------|------|-------------|
| `Coupn_Id` | string (doc ID) | Coupn_Id |
| `Coupn_Code` | string | Coupn_Code |
| `Coupn_Description` | string | Coupn_Description |
| `Coupn_DiscountValue` | float | Coupn_DiscountValue |
| `Coupn_MinOrderAmount` | float | Coupn_MinOrderAmount |
| `Coupn_MaxDiscount` | float | Coupn_MaxDiscount |
| `Coupn_ExpiryDate` | timestamp | Coupn_ExpiryDate |
| `Coupn_IsActive` | bool | Coupn_IsActive |

---

### 7. `customers/{uid}/cartItems` → subcollection replacing `CartItem` table

**Subcollection** under each customer document. Auto-generated document ID.

**Key difference from MySQL:** `Menu_Name` and `Menu_ImageURL` are **denormalized** into the cart item document to avoid N+1 reads (no JOINs).

| Field | Type | MySQL Source |
|-------|------|-------------|
| `Cart_Id` | string (doc ID) | Cart_Id |
| `Cart_Cust_Id` | string | = parent customer UID |
| `Cart_Menu_MenuItemId` | string | Menu_MenuItemId (FK → menuItems doc ID) |
| `Cart_Quantity` | int | Cart_Quantity |
| `Cart_ItemPrice` | float | Cart_ItemPrice |
| `Cart_Total` | float | Cart_Total |
| `Menu_Name` | string | **Denormalized** from McdoMenuItem |
| `Menu_ImageURL` | string | **Denormalized** from McdoMenuItem |

**Example document:**
```json
{
  "Cart_Id": "abc123",
  "Cart_Cust_Id": "uid123",
  "Cart_Menu_MenuItemId": "menu456",
  "Cart_Quantity": 2,
  "Cart_ItemPrice": 199.00,
  "Cart_Total": 398.00,
  "Menu_Name": "Big Mac",
  "Menu_ImageURL": "bigmac.jpg"
}
```

**Why denormalize?** The bag sidebar (`footer.php`) needs product name + image for every cart item. Without denormalization, displaying 5 cart items would require:
- 1 query for cart items + 5 queries for product details = 6 reads  
With denormalization: 1 query for cart items = 1 read.

---

### 8. `orders` → replaces `McOrder` + `orderitem` + `Payment` + `McDeliveryStatus`

**The biggest structural change.** `orderitem`, `Payment`, and `McDeliveryStatus` tables are **embedded inside** the order document instead of being separate tables.

| Field | Type | MySQL Source |
|-------|------|-------------|
| `Order_Id` | string (doc ID) | Order_Id |
| `Order_Cust_Id` | string | Cust_Id (FK → customers) |
| `Order_Coup_Id` | string? | Coupn_Id (nullable) |
| `Order_Add_Id` | string | Add_Id (FK → addresses) |
| `Order_Brnch_Id` | string | Brnch_Id (FK → branches) |
| `Order_OrderDate` | timestamp | Order_OrderDate |
| `Order_Status` | string | Order_Status |
| `Order_TotalAmount` | float | Order_TotalAmount |
| `Order_Quantity` | int | Order_Quantity |
| `Order_DeliveryFee` | float | Order_DeliveryFee |
| `Order_PrepTime` | int | Order_PrepTime |
| `items` | array | **Embedded** from orderitem table |
| `payment` | object | **Embedded** from Payment table |
| `deliveryStatus` | array | **Embedded** from McDeliveryStatus table |
| `Cust_FName` | string | **Denormalized** from Customer (at order time) |
| `Cust_LName` | string | **Denormalized** from Customer (at order time) |
| `Add_Street` | string | **Denormalized** from Address (at order time) |
| `Add_Barangay` | string | **Denormalized** from Address (at order time) |
| `Add_City` | string | **Denormalized** from Address (at order time) |
| `Add_Municipality` | string | **Denormalized** from Address (at order time) |
| `Add_PostalCode` | string | **Denormalized** from Address (at order time) |
| `Brnch_Name` | string | **Denormalized** from branch (at order time) |
| `Brnch_Street` | string | **Denormalized** from branch (at order time) |
| `Brnch_City` | string | **Denormalized** from branch (at order time) |

#### Embedded `items` array (replaces `orderitem` table)

Each element:
```json
{
  "OrderItem_MenuItemId": "menu456",
  "Menu_Name": "Big Mac",
  "Menu_ImageURL": "bigmac.jpg",
  "OrderItem_Quantity": 2,
  "OrderItem_Price": 199.00,
  "OrderItem_Total": 398.00
}
```

#### Embedded `payment` object (replaces `Payment` table)

```json
{
  "Pay_PaymentType": "Cash on Delivery",
  "Pay_PaymentStatus": "Pending",
  "Pay_PaidAmount": 597.00,
  "Pay_TransactionDate": "2025-01-15T10:30:00Z"
}
```

#### Embedded `deliveryStatus` array (replaces `McDeliveryStatus` table)

```json
[
  { "Dlvry_StatusUpdate": "Order Placed", "Dlvry_DateTime": "2025-01-15T10:30:00Z" },
  { "Dlvry_StatusUpdate": "Preparing", "Dlvry_DateTime": "2025-01-15T10:35:00Z" }
]
```

**Example full order document:**
```json
{
  "Order_Id": "order789",
  "Order_Cust_Id": "uid123",
  "Order_Add_Id": "addr456",
  "Order_Brnch_Id": "branch001",
  "Order_OrderDate": "2025-01-15T10:30:00Z",
  "Order_Status": "Preparing",
  "Order_TotalAmount": 597.00,
  "Order_Quantity": 3,
  "Order_DeliveryFee": 49.00,
  "Order_PrepTime": 15,
  "Cust_FName": "Juan",
  "Cust_LName": "Dela Cruz",
  "Add_Street": "123 Rizal Ave",
  "Add_Barangay": "Barangay 1",
  "Add_City": "Manila",
  "Add_Municipality": "Manila",
  "Add_PostalCode": "1000",
  "Brnch_Name": "McDo Manila Main",
  "Brnch_Street": "123 Rizal Ave",
  "Brnch_City": "Manila",
  "items": [
    {
      "OrderItem_MenuItemId": "menu456",
      "Menu_Name": "Big Mac",
      "Menu_ImageURL": "bigmac.jpg",
      "OrderItem_Quantity": 2,
      "OrderItem_Price": 199.00,
      "OrderItem_Total": 398.00
    },
    {
      "OrderItem_MenuItemId": "menu789",
      "Menu_Name": "Fries (Large)",
      "Menu_ImageURL": "fries_large.jpg",
      "OrderItem_Quantity": 1,
      "OrderItem_Price": 99.00,
      "OrderItem_Total": 99.00
    }
  ],
  "payment": {
    "Pay_PaymentType": "Cash on Delivery",
    "Pay_PaymentStatus": "Pending",
    "Pay_PaidAmount": 597.00,
    "Pay_TransactionDate": "2025-01-15T10:30:00Z"
  },
  "deliveryStatus": [
    { "Dlvry_StatusUpdate": "Order Placed", "Dlvry_DateTime": "2025-01-15T10:30:00Z" }
  ]
}
```

---

### 9. `riders` → replaces `DeliveryRider` table

Separate collection. Auto-generated document ID.  
*(Currently not used by code, modeled for future use.)*

---

### 10. `branchStats/{branchId}` → NEW (for admin dashboard)

Counter documents to avoid scanning all orders for dashboard stats.  
Document ID = branch document ID (matching `branches` collection).

| Field | Type |
|-------|------|
| `totalOrders` | int |
| `revenue` | float |
| `pendingOrders` | int |
| `preparingOrders` | int |
| `readyOrders` | int |
| `completedOrders` | int |
| `lastUpdated` | timestamp |

**Updated atomically** during `mcd_checkout()` and `mcd_update_order_status()` using `Firestore::increment()`.

---

## Indexes Required

Create these composite indexes in Firebase Console → Firestore → Indexes:

| Collection | Fields | Use Case |
|------------|--------|----------|
| `orders` | `Order_Cust_Id` ASC, `Order_OrderDate` DESC | Customer order history |
| `orders` | `Order_Brnch_Id` ASC, `Order_OrderDate` DESC | Kitchen orders + admin dashboard |
| `orders` | `Order_Brnch_Id` ASC, `Order_Status` ASC, `Order_OrderDate` DESC | Kitchen order filtering by status |
| `addresses` | `Add_Cust_Id` ASC | Customer address listing |
| `staff` | `Staff_Brnch_Id` ASC | Branch staff listing |

---

## Field Names — Key to Keep MySQL Compatibility

All field names use **the same PascalCase names** as MySQL columns so existing PHP code (`$order['Order_Status']`, `$item['Menu_Name']`, etc.) works unchanged.

The **only exception** is the `items` array (from `orderitem` table) — it's accessed as `$order['items']` instead of a separate query.

---

## What Tables "Disappear" (Embedded)

| MySQL Table | Firestore Location |
|-------------|-------------------|
| `orderitem` | Embedded as `items` array in `orders` doc |
| `Payment` | Embedded as `payment` object in `orders` doc |
| `McDeliveryStatus` | Embedded as `deliveryStatus` array in `orders` doc |

---

## Migration Script Design

The migration script (`migrate_to_firestore.php`) will:

1. **Customers:** Read from MySQL → create in Firebase Auth → create doc in `customers` collection → build `old_Cust_Id → Firebase_UID` mapping
2. **Branches, Menu Items, Coupons:** Read from MySQL → create docs in respective collections with old INT IDs as document ID strings
3. **Staff:** Read from MySQL → create in Firebase Auth → create `staff` doc
4. **Addresses:** Replace `Add_Cust_Id` (INT) with Firebase UID using mapping
5. **Cart Items:** Replace `Cart_Cust_Id` with Firebase UID, denormalize Menu_Name/ImageURL
6. **Orders:** Replace IDs with Firebase UIDs, embed items/payment/status, denormalize customer/address/branch info

---

## PHP Function Mapping — cart.php Returns

Every function keeps its **exact return structure**:

| Function | Return Keys (unchanged) |
|----------|------------------------|
| `mcd_get_customer_bag_items()` | `['id', 'name', 'price', 'image', 'quantity', 'total']` |
| `mcd_get_customer_orders()` | `['Order_Id', 'Order_Status', 'Brnch_Name', 'items' => [...], 'LatestStatus', ...]` |
| `mcd_get_kitchen_orders()` | `['Order_Id', 'Cust_FName', 'Cust_LName', 'Add_Street', 'items' => [...], ...]` |
| `mcd_get_order_items()` | `['OrderItem_MenuItemId', 'Menu_Name', 'Menu_ImageURL', 'OrderItem_Quantity', 'OrderItem_Price', 'OrderItem_Total']` |
| `mcd_get_payment_info()` | `['Pay_PaymentType', 'Pay_PaymentStatus', 'Pay_PaidAmount', 'Pay_TransactionDate']` |
