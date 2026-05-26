<?php
session_start();
include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!$firebaseInitialized) {
        $_SESSION['login_error'] = "Firebase not configured.";
        $_SESSION['show_login_modal'] = true;
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }

    try {
        $result = $auth->signInWithEmailAndPassword($email, $password);
        $uid = $result->firebaseUserId();

        // Read customer data from Firestore
        $db = $firestore->database();
        $customerDoc = $db->collection('customers')->document($uid)->snapshot();

        if ($customerDoc->exists()) {
            $customer = $customerDoc->data();
            $_SESSION['Cust_Id'] = $uid;
            $_SESSION['Cust_FName'] = $customer['Cust_FName'] ?? '';
            header("Location: branch_select.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Account not found.";
        }
    } catch (\Kreait\Firebase\Auth\SignIn\FailedToSignIn $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'EMAIL_NOT_FOUND') !== false) {
            $_SESSION['login_error'] = "No account found with that email.";
        } elseif (strpos($errorMsg, 'INVALID_PASSWORD') !== false) {
            $_SESSION['login_error'] = "Wrong password.";
        } elseif (strpos($errorMsg, 'INVALID_LOGIN_CREDENTIALS') !== false) {
            $_SESSION['login_error'] = "Invalid email or password.";
        } else {
            $_SESSION['login_error'] = "Login failed. Please try again.";
        }
    } catch (\Exception $e) {
        $_SESSION['login_error'] = "Login failed. Please try again.";
    }

    $_SESSION['show_login_modal'] = true;
    $_SESSION['login_email'] = $email;
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $referrer");
    exit();
}
?>
