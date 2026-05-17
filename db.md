# McDelivery Web - Database Schema

## Database Information

| Setting | Value |
|---------|-------|
| **Database Name** | `mcd_db` |
| **Host** | `localhost` |
| **Username** | `root` (default) |
| **Password** | (empty by default) |
| **Charset** | `utf8mb4` |

Connection is configured in `config/db.php`.

---

## Setup Instructions

1. Open **phpMyAdmin** or your MySQL client
2. Create a new database named `mcd_db`:
   ```sql
   CREATE DATABASE IF NOT EXISTS mcd_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   USE mcd_db;
   ```
3. Run all **CREATE TABLE** statements below in order (they are ordered to respect foreign key dependencies)
4. Run the **Sample Data** INSERT statements to populate initial data
5. Copy the `uploads/` folder from the project to the new environment (it contains product images)

---

## Tables (in creation order)

### 1. McBranch
Stores branch/outlet information.

```sql
CREATE TABLE McBranch (
    Brnch_Id INT AUTO_INCREMENT PRIMARY KEY,
    Brnch_Street VARCHAR(255),
    Brnch_Barangay VARCHAR(255),
    Brnch_City VARCHAR(255),
    Brnch_Municipality VARCHAR(255),
    Brnch_PostalCode VARCHAR(10),
    Brnch_Phone VARCHAR(20)
);
```

| Column | Type | Description |
|--------|------|-------------|
| Brnch_Id | INT (AUTO_INCREMENT) | Primary Key |
| Brnch_Street | VARCHAR(255) | Street address |
| Brnch_Barangay | VARCHAR(255) | Barangay/district |
| Brnch_City | VARCHAR(255) | City |
| Brnch_Municipality | VARCHAR(255) | Municipality |
| Brnch_PostalCode | VARCHAR(10) | ZIP/Postal code |
| Brnch_Phone | VARCHAR(20) | Contact number |

---

### 2. Customer
Stores customer/end-user accounts.

```sql
CREATE TABLE Customer (
    Cust_Id INT AUTO_INCREMENT PRIMARY KEY,
    Cust_FName VARCHAR(100),
    Cust_LName VARCHAR(100),
    Cust_Email VARCHAR(150) UNIQUE,
    Cust_Password VARCHAR(255),
    Cust_Phone VARCHAR(20),
    Cust_CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

| Column | Type | Description |
|--------|------|-------------|
| Cust_Id | INT (AUTO_INCREMENT) | Primary Key |
| Cust_FName | VARCHAR(100) | First name |
| Cust_LName | VARCHAR(100) | Last name |
| Cust_Email | VARCHAR(150) | Email (UNIQUE) |
| Cust_Password | VARCHAR(255) | bcrypt hashed password |
| Cust_Phone | VARCHAR(20) | Contact number |
| Cust_CreatedAt | TIMESTAMP | Auto-set registration date |

---

### 3. Staff
Stores admin and kitchen staff accounts.

```sql
CREATE TABLE Staff (
    Staff_Id INT AUTO_INCREMENT PRIMARY KEY,
    Staff_Brnch_Id INT,
    Staff_Role VARCHAR(50),
    Staff_FName VARCHAR(100),
    Staff_LName VARCHAR(100),
    Staff_Phone VARCHAR(20),
    Staff_Email VARCHAR(150),
    Staff_Password VARCHAR(255),
    FOREIGN KEY (Staff_Brnch_Id) REFERENCES McBranch(Brnch_Id)
);
```

| Column | Type | Description |
|--------|------|-------------|
| Staff_Id | INT (AUTO_INCREMENT) | Primary Key |
| Staff_Brnch_Id | INT | FK -> McBranch.Brnch_Id |
| Staff_Role | VARCHAR(50) | 'Admin' or 'Kitchen Staff' |
| Staff_FName | VARCHAR(100) | First name |
| Staff_LName | VARCHAR(100) | Last name |
| Staff_Phone | VARCHAR(20) | Contact number |
| Staff_Email | VARCHAR(150) | Login email |
| Staff_Password | VARCHAR(255) | bcrypt hashed password |

---

### 4. Address
Stores customer delivery addresses.

```sql
CREATE TABLE Address (
    Add_Id INT AUTO_INCREMENT PRIMARY KEY,
    Add_Cust_Id INT,
    Add_Street VARCHAR(255),
    Add_Barangay VARCHAR(255),
    Add_City VARCHAR(255),
    Add_Municipality VARCHAR(255),
    Add_PostalCode VARCHAR(10),
    FOREIGN KEY (Add_Cust_Id) REFERENCES Customer(Cust_Id)
);
```

| Column | Type | Description |
|--------|------|-------------|
| Add_Id | INT (AUTO_INCREMENT) | Primary Key |
| Add_Cust_Id | INT | FK -> Customer.Cust_Id |
| Add_Street | VARCHAR(255) | Street/house number |
| Add_Barangay | VARCHAR(255) | Barangay |
| Add_City | VARCHAR(255) | City |
| Add_Municipality | VARCHAR(255) | Municipality |
| Add_PostalCode | VARCHAR(10) | ZIP code |

---

### 5. McdoMenuItem
Stores food/drink products available for order.

```sql
CREATE TABLE McdoMenuItem (
    Menu_MenuItemId INT AUTO_INCREMENT PRIMARY KEY,
    Menu_Name VARCHAR(255),
    Menu_Description TEXT,
    Menu_Price DECIMAL(10,2),
    Menu_Category VARCHAR(100),
    Menu_ImageURL VARCHAR(255),
    Menu_Available BOOLEAN DEFAULT 1
);
```

| Column | Type | Description |
|--------|------|-------------|
| Menu_MenuItemId | INT (AUTO_INCREMENT) | Primary Key |
| Menu_Name | VARCHAR(255) | Product name |
| Menu_Description | TEXT | Product description |
| Menu_Price | DECIMAL(10,2) | Price in PHP |
| Menu_Category | VARCHAR(100) | e.g. 'Burgers', 'Chicken', 'Breakfast' |
| Menu_ImageURL | VARCHAR(255) | Filename in `uploads/` folder |
| Menu_Available | BOOLEAN | 1 = visible, 0 = hidden |

**Note:** Table name is case-insensitive on Windows — queries use both `mcdomenuitem` and `McdoMenuItem`.

---

### 6. McdoCoupon
Stores discount coupons. *(Currently defined but not actively used by the application code.)*

```sql
CREATE TABLE McdoCoupon (
    Coupn_Id INT AUTO_INCREMENT PRIMARY KEY,
    Coupn_Code VARCHAR(50) UNIQUE,
    Coupn_Description TEXT,
    Coupn_DiscountValue DECIMAL(10,2),
    Coupn_MinOrderAmount DECIMAL(10,2),
    Coupn_MaxDiscount DECIMAL(10,2),
    Coupn_ExpiryDate DATE,
    Coupn_IsActive BOOLEAN DEFAULT 1
);
```

| Column | Type | Description |
|--------|------|-------------|
| Coupn_Id | INT (AUTO_INCREMENT) | Primary Key |
| Coupn_Code | VARCHAR(50) | Unique coupon code |
| Coupn_Description | TEXT | Description |
| Coupn_DiscountValue | DECIMAL(10,2) | Discount amount |
| Coupn_MinOrderAmount | DECIMAL(10,2) | Minimum order to apply |
| Coupn_MaxDiscount | DECIMAL(10,2) | Maximum discount cap |
| Coupn_ExpiryDate | DATE | Expiration date |
| Coupn_IsActive | BOOLEAN | 1 = active, 0 = inactive |

---

### 7. CartItem
Stores items in a customer's shopping cart before checkout.

```sql
CREATE TABLE CartItem (
    Cart_Id INT AUTO_INCREMENT PRIMARY KEY,
    Cart_Cust_Id INT,
    Cart_Menu_MenuItemId INT,
    Cart_Quantity INT,
    Cart_ItemPrice DECIMAL(10,2),
    Cart_Total DECIMAL(10,2),
    FOREIGN KEY (Cart_Cust_Id) REFERENCES Customer(Cust_Id),
    FOREIGN KEY (Cart_Menu_MenuItemId) REFERENCES McdoMenuItem(Menu_MenuItemId)
);
```

| Column | Type | Description |
|--------|------|-------------|
| Cart_Id | INT (AUTO_INCREMENT) | Primary Key |
| Cart_Cust_Id | INT | FK -> Customer.Cust_Id |
| Cart_Menu_MenuItemId | INT | FK -> McdoMenuItem.Menu_MenuItemId |
| Cart_Quantity | INT | Quantity |
| Cart_ItemPrice | DECIMAL(10,2) | Unit price at time of adding |
| Cart_Total | DECIMAL(10,2) | Line total (qty x price) |

---

### 8. McOrder
Stores customer orders after checkout.

```sql
CREATE TABLE McOrder (
    Order_Id INT AUTO_INCREMENT PRIMARY KEY,
    Order_Cust_Id INT,
    Order_Coup_Id INT NULL,
    Order_Add_Id INT,
    Order_OrderDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Order_Status VARCHAR(50),
    Order_TotalAmount DECIMAL(10,2),
    Order_Quantity INT,
    Order_DeliveryFee DECIMAL(10,2),
    Order_PrepTime INT,
    FOREIGN KEY (Order_Cust_Id) REFERENCES Customer(Cust_Id),
    FOREIGN KEY (Order_Coup_Id) REFERENCES McdoCoupon(Coupn_Id),
    FOREIGN KEY (Order_Add_Id) REFERENCES Address(Add_Id)
);
```

| Column | Type | Description |
|--------|------|-------------|
| Order_Id | INT (AUTO_INCREMENT) | Primary Key |
| Order_Cust_Id | INT | FK -> Customer.Cust_Id |
| Order_Coup_Id | INT | FK -> McdoCoupon.Coupn_Id (nullable) |
| Order_Add_Id | INT | FK -> Address.Add_Id (delivery address) |
| Order_OrderDate | TIMESTAMP | Order placement date/time |
| Order_Status | VARCHAR(50) | 'Pending', 'Preparing', 'Ready', 'Completed' |
| Order_TotalAmount | DECIMAL(10,2) | Grand total (subtotal + delivery fee) |
| Order_Quantity | INT | Total item count |
| Order_DeliveryFee | DECIMAL(10,2) | Flat delivery fee (₱49) |
| Order_PrepTime | INT | Preparation time in minutes |

---

### 9. orderitem
Stores individual line items within an order.

```sql
CREATE TABLE orderitem (
    OrderItem_Id INT AUTO_INCREMENT PRIMARY KEY,
    OrderItem_Order_Id INT,
    OrderItem_MenuItemId INT,
    OrderItem_Quantity INT,
    OrderItem_Price DECIMAL(10,2),
    OrderItem_Total DECIMAL(10,2),
    FOREIGN KEY (OrderItem_Order_Id) REFERENCES McOrder(Order_Id),
    FOREIGN KEY (OrderItem_MenuItemId) REFERENCES McdoMenuItem(Menu_MenuItemId)
);
```

| Column | Type | Description |
|--------|------|-------------|
| OrderItem_Id | INT (AUTO_INCREMENT) | Primary Key |
| OrderItem_Order_Id | INT | FK -> McOrder.Order_Id |
| OrderItem_MenuItemId | INT | FK -> McdoMenuItem.Menu_MenuItemId |
| OrderItem_Quantity | INT | Quantity ordered |
| OrderItem_Price | DECIMAL(10,2) | Unit price |
| OrderItem_Total | DECIMAL(10,2) | Line total (qty x price) |

---

### 10. McDeliveryStatus
Tracks delivery/order status updates over time.

```sql
CREATE TABLE McDeliveryStatus (
    Dlvry_Id INT AUTO_INCREMENT PRIMARY KEY,
    Dlvry_Order_Id INT,
    Dlvry_StatusUpdate VARCHAR(100),
    Dlvry_DateTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Dlvry_Order_Id) REFERENCES McOrder(Order_Id)
);
```

| Column | Type | Description |
|--------|------|-------------|
| Dlvry_Id | INT (AUTO_INCREMENT) | Primary Key |
| Dlvry_Order_Id | INT | FK -> McOrder.Order_Id |
| Dlvry_StatusUpdate | VARCHAR(100) | e.g. 'Order Placed', 'Preparing' |
| Dlvry_DateTime | TIMESTAMP | Timestamp of update |

---

### 11. DeliveryRider
Stores rider information. *(Currently defined but not actively used by the application code.)*

```sql
CREATE TABLE DeliveryRider (
    Rider_Id INT AUTO_INCREMENT PRIMARY KEY,
    Rider_Dlvry_Id INT,
    Rider_Name VARCHAR(150),
    Rider_Phone VARCHAR(20),
    Rider_Email VARCHAR(150),
    Rider_Status VARCHAR(50),
    FOREIGN KEY (Rider_Dlvry_Id) REFERENCES McDeliveryStatus(Dlvry_Id)
);
```

| Column | Type | Description |
|--------|------|-------------|
| Rider_Id | INT (AUTO_INCREMENT) | Primary Key |
| Rider_Dlvry_Id | INT | FK -> McDeliveryStatus.Dlvry_Id |
| Rider_Name | VARCHAR(150) | Rider name |
| Rider_Phone | VARCHAR(20) | Contact number |
| Rider_Email | VARCHAR(150) | Email |
| Rider_Status | VARCHAR(50) | Status |

---

### 12. Payment
Stores payment information for each order.

```sql
CREATE TABLE Payment (
    Pay_Id INT AUTO_INCREMENT PRIMARY KEY,
    Pay_Order_Id INT,
    Pay_PaymentType VARCHAR(50),
    Pay_PaymentStatus VARCHAR(50),
    Pay_PaidAmount DECIMAL(10,2),
    Pay_TransactionDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Pay_Order_Id) REFERENCES McOrder(Order_Id)
);
```

| Column | Type | Description |
|--------|------|-------------|
| Pay_Id | INT (AUTO_INCREMENT) | Primary Key |
| Pay_Order_Id | INT | FK -> McOrder.Order_Id |
| Pay_PaymentType | VARCHAR(50) | e.g. 'Cash on Delivery' |
| Pay_PaymentStatus | VARCHAR(50) | 'Pending' or 'Done' |
| Pay_PaidAmount | DECIMAL(10,2) | Amount paid |
| Pay_TransactionDate | TIMESTAMP | Payment date |

---

## Foreign Key Relationships Diagram

```
McBranch ────< Staff
Customer ────< Address
Customer ────< CartItem
Customer ────< McOrder
McdoMenuItem ────< CartItem
McdoMenuItem ────< orderitem
McdoCoupon ────< McOrder
Address ────< McOrder
McOrder ────< orderitem
McOrder ────< McDeliveryStatus
McOrder ────< Payment
McDeliveryStatus ────< DeliveryRider
```

---

## Sample Data

### Branches
```sql
INSERT INTO McBranch (Brnch_Street, Brnch_Barangay, Brnch_City, Brnch_Municipality, Brnch_PostalCode, Brnch_Phone)
VALUES
('123 Rizal Ave', 'Barangay 1', 'Manila', 'Manila', '1000', '09123456789'),
('456 Mabini St', 'Barangay 2', 'Quezon City', 'Quezon City', '1100', '09987654321');
```

### Admin Staff Account
Generate a bcrypt hash for the password using PHP:
```bash
php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
```
Then insert:
```sql
INSERT INTO Staff (Staff_Brnch_Id, Staff_Role, Staff_FName, Staff_LName, Staff_Phone, Staff_Email, Staff_Password)
VALUES (1, 'Admin', 'Admin', 'User', '09111111111', 'admin@mcdelivery.com', 'REPLACE_WITH_HASHED_PASSWORD');
```

**Default login:** `admin@mcdelivery.com` / `admin123`

### Sample Menu Items
```sql
INSERT INTO McdoMenuItem (Menu_Name, Menu_Description, Menu_Price, Menu_Category, Menu_ImageURL, Menu_Available)
VALUES
('Big Mac', 'Two all-beef patties, special sauce, lettuce, cheese, pickles, onions on a sesame seed bun', 199.00, 'Burgers', 'bigmac.jpg', 1),
('McChicken', 'Crispy chicken fillet with lettuce and mayonnaise', 149.00, 'Burgers', 'mcchicken.jpg', 1),
('10pc Chicken McNuggets', 'Tender chicken McNuggets with your choice of dip', 229.00, 'Chicken', '10pcnuggets.jpg', 1),
('McSpaghetti', 'Spaghetti with ground meat sauce and grated cheese', 85.00, 'McSpaghetti', 'mcspaghetti.jpg', 1),
('Fries (Large)', 'Hot and crispy golden fries', 99.00, 'Fries & Extras', 'fries_large.jpg', 1),
('Chocolate Sundae', 'Creamy vanilla soft serve with chocolate topping', 49.00, 'Desserts & Drinks', 'choco_sundae.jpg', 1),
('Coke Float', 'Coca-Cola with creamy vanilla soft serve', 55.00, 'Desserts & Drinks', 'coke_float.jpg', 1),
('Iced Coffee', 'Brewed iced coffee with sweet cream', 75.00, 'McCafe', 'iced_coffee.jpg', 1),
('Happy Meal Burger', 'Burger with fries, drink and a toy', 149.00, 'Happy Meal', 'happymeal_burger.jpg', 1),
('Chicken McDo with Rice', 'Crispy chicken with steamed rice and gravy', 135.00, 'Chicken', 'chicken_rice.jpg', 1),
('2pc Chicken with Rice', 'Two pieces of crispy chicken with rice and gravy', 175.00, 'Chicken', '2pc_chicken.jpg', 1),
('Quarter Pounder with Cheese', 'Quarter pound beef patty with cheese, pickles, onions and ketchup', 215.00, 'Burgers', 'quarter_pounder.jpg', 1),
('Spaghetti with Chicken', 'McSpaghetti with a piece of crispy chicken', 159.00, 'McSpaghetti', 'spag_chicken.jpg', 1),
('French Fries (Medium)', 'Classic golden fries', 75.00, 'Fries & Extras', 'fries_medium.jpg', 1),
('Hot Fudge Sundae', 'Vanilla soft serve with hot fudge', 55.00, 'Desserts & Drinks', 'hotfudge_sundae.jpg', 1),
('Caramel Sundae', 'Vanilla soft serve with caramel topping', 55.00, 'Desserts & Drinks', 'caramel_sundae.jpg', 1);
```

### Hompage Categories (shown conditionally by time of day)
```sql
INSERT INTO McdoMenuItem (Menu_Name, Menu_Description, Menu_Price, Menu_Category, Menu_ImageURL, Menu_Available)
VALUES
-- Dinner Specials (shown on homepage as "breakfast/lunch/dinner" based on time)
('Chicken McDo Dinner', 'Crispy chicken dinner plate with rice and gravy', 175.00, 'Dinner Specials', 'dinner_chicken.jpg', 1),
('Big Mac Dinner', 'Big Mac with fries and drink', 249.00, 'Dinner Specials', 'dinner_bigmac.jpg', 1),
('Spaghetti Dinner', 'McSpaghetti with rice and drink', 199.00, 'Dinner Specials', 'dinner_spag.jpg', 1),

-- McDelivery Exclusives
('BFF Bundle', '2 burgers, 2 fries, 2 drinks and 6pc nuggets', 429.00, 'Exclusives', 'bff_bundle.jpg', 1),
('Family Bucket', '8pc chicken, 4 rice, 4 gravy, large fries and 1.5L drink', 699.00, 'Exclusives', 'family_bucket.jpg', 1),

-- Featured
('McDo Burger Steak', 'Beef patty with mushroom gravy and rice', 129.00, 'Featured', 'burger_steak.jpg', 1),
('Chicken Ala King', 'Creamy chicken with vegetables on rice', 139.00, 'Featured', 'chicken_ala_king.jpg', 1),

-- Breakfast (always available, shown on menu page)
('Longganisa with Egg', 'Filipino-style sausage with fried egg and rice', 99.00, 'Breakfast', 'longganisa_egg.jpg', 1),
('Beef Tapa with Egg', 'Beef tapa with fried egg and garlic rice', 109.00, 'Breakfast', 'beef_tapa.jpg', 1),
('Pancakes with Syrup', 'Stack of pancakes with butter and syrup', 79.00, 'Breakfast', 'pancakes.jpg', 1);
```

---

## Notes

- **Table names** in MySQL on Windows are case-insensitive. The application uses both lowercase (e.g. `mcdomenuitem`) and PascalCase (e.g. `McdoMenuItem`) — both work.
- **`uploads/` folder**: Product images referenced by `Menu_ImageURL` must exist in the `uploads/` directory at the project root. Without these image files, products will show broken image placeholders.
- **Password hashing**: All passwords (customers and staff) are stored as bcrypt hashes using PHP's `password_hash()`. The `rand` column is not used.
