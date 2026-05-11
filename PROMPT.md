Task: Make productdetails.php dynamic by fetching data from the MySQL database based on the clicked item.

1. Header Fix: > - Fix the current header to be position: fixed at the top of the viewport.


2. Data Fetching: > - Use the GET method to retrieve the product ID from the URL (e.g., productdetails.php?id=12).

Write a Prepared Statement to select all columns from the Menu table where Menu_MenuItemId matches the ID.

Store the result in a variable called $product.

3. Dynamic UI Binding:

Replace the hardcoded text "Cheeseburger Meal" with <?php echo $product['Menu_Name']; ?>.

Replace the price with <?php echo $product['Menu_Price']; ?>.

Replace the static image source with the image path stored in your database column.

4. Navigation:

Ensure that on the User Dashboard (Menu Page), every product card is wrapped in a link that passes the ID: <a href="productdetails.php?id=<?php echo $row['Menu_MenuItemId']; ?>">.