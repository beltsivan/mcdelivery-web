<?php
session_start();
include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM Customer WHERE Cust_Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['Cust_Password'])) {
            $_SESSION['Cust_Id'] = $user['Cust_Id'];
            $_SESSION['Cust_FName'] = $user['Cust_FName'];
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Wrong password.";
        }
    } else {
        $_SESSION['login_error'] = "No account found with that email.";
    }

    $_SESSION['show_login_modal'] = true;
    $_SESSION['login_email'] = $email;
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $referrer");
    exit();
}
?>