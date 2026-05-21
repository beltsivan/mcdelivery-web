-- 1. McBranch
CREATE TABLE McBranch (
    Brnch_Id INT AUTO_INCREMENT PRIMARY KEY,
    Brnch_Name VARCHAR(255) NOT NULL,
    Brnch_Street VARCHAR(255),
    Brnch_Barangay VARCHAR(255),
    Brnch_City VARCHAR(255),
    Brnch_Municipality VARCHAR(255),
    Brnch_PostalCode VARCHAR(10),
    Brnch_Phone VARCHAR(20)
);

-- 2. Customer
CREATE TABLE Customer (
    Cust_Id INT AUTO_INCREMENT PRIMARY KEY,
    Cust_FName VARCHAR(100),
    Cust_LName VARCHAR(100),
    Cust_Email VARCHAR(150) UNIQUE,
    Cust_Password VARCHAR(255),
    Cust_Phone VARCHAR(20),
    Cust_CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Staff
CREATE TABLE Staff (
    Staff_Id INT AUTO_INCREMENT PRIMARY KEY,
    Staff_Brnch_Id INT,
    Staff_Role VARCHAR(50),
    Staff_FName VARCHAR(100),
    Staff_LName VARCHAR(100),
    Staff_Phone VARCHAR(20),
    Staff_Email VARCHAR(150),
    FOREIGN KEY (Staff_Brnch_Id) REFERENCES McBranch(Brnch_Id)
);

-- 4. Address
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

-- 5. McdoMenuItem
CREATE TABLE McdoMenuItem (
    Menu_MenuItemId INT AUTO_INCREMENT PRIMARY KEY,
    Menu_Name VARCHAR(255),
    Menu_Description TEXT,
    Menu_Price DECIMAL(10,2),
    Menu_Category VARCHAR(100),
    Menu_ImageURL VARCHAR(255),
    Menu_Available BOOLEAN DEFAULT 1
);

-- 9. McdoCoupon (Created before McOrder because of FK)
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

-- 7. McOrder
CREATE TABLE McOrder (
    Order_Id INT AUTO_INCREMENT PRIMARY KEY,
    Order_Cust_Id INT,
    Order_Coup_Id INT NULL,
    Order_OrderDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Order_Status VARCHAR(50),
    Order_TotalAmount DECIMAL(10,2),
    Order_Quantity INT,
    Order_DeliveryFee DECIMAL(10,2),
    FOREIGN KEY (Order_Cust_Id) REFERENCES Customer(Cust_Id),
    FOREIGN KEY (Order_Coup_Id) REFERENCES McdoCoupon(Coupn_Id)
);

-- 6. CartItem
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

-- 8. McDeliveryStatus
CREATE TABLE McDeliveryStatus (
    Dlvry_Id INT AUTO_INCREMENT PRIMARY KEY,
    Dlvry_Order_Id INT,
    Dlvry_StatusUpdate VARCHAR(100),
    Dlvry_DateTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Dlvry_Order_Id) REFERENCES McOrder(Order_Id)
);

-- 10. DeliveryRider
CREATE TABLE DeliveryRider (
    Rider_Id INT AUTO_INCREMENT PRIMARY KEY,
    Rider_Dlvry_Id INT,
    Rider_Name VARCHAR(150),
    Rider_Phone VARCHAR(20),
    Rider_Email VARCHAR(150),
    Rider_Status VARCHAR(50),
    FOREIGN KEY (Rider_Dlvry_Id) REFERENCES McDeliveryStatus(Dlvry_Id)
);

-- 11. Payment
CREATE TABLE Payment (
    Pay_Id INT AUTO_INCREMENT PRIMARY KEY,
    Pay_Order_Id INT,
    Pay_PaymentType VARCHAR(50),
    Pay_PaymentStatus VARCHAR(50),
    Pay_PaidAmount DECIMAL(10,2),
    Pay_TransactionDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Pay_Order_Id) REFERENCES McOrder(Order_Id)
);


-- 1. Add branch column to orders table
ALTER TABLE mcorder ADD COLUMN Order_Brnch_Id INT NULL AFTER Order_Add_Id;
-- 2. Convert non-system-admin Admin accounts to Manager role
UPDATE staff SET Staff_Role = 'Manager' WHERE Staff_Email != 'admin@gmail.com' AND Staff_Role = 'Admin';
