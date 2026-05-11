<?php
session_start();
include('config/db.php');  // Connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Look for the user in the database
    $stmt = $conn->prepare("SELECT * FROM Customer WHERE Cust_Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 2. Check if the password is correct
        if (password_verify($password, $user['Cust_Password'])) {
            
            // 3. SUCCESS! Save user info into the "Session"
            $_SESSION['Cust_Id'] = $user['Cust_Id'];
            $_SESSION['Cust_FName'] = $user['Cust_FName'];
            
            // Go back to the home page
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    
    // If there's an error, send them back to index with a message
    header("Location: index.php?login_error=" . urlencode($error));
    exit();
}
?>