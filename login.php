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
        // Use Firebase Auth REST API to verify credentials
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=$firebaseApiKey";
        $payload = json_encode([
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['idToken'])) {
            // Verify the ID token and get the UID
            $verifiedToken = $auth->verifyIdToken($data['idToken']);
            $uid = $verifiedToken->claims()->get('sub');

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
        } else {
            $errorMsg = $data['error']['message'] ?? '';
            if (strpos($errorMsg, 'EMAIL_NOT_FOUND') !== false) {
                $_SESSION['login_error'] = "No account found with that email.";
            } elseif (strpos($errorMsg, 'INVALID_PASSWORD') !== false) {
                $_SESSION['login_error'] = "Wrong password.";
            } elseif (strpos($errorMsg, 'INVALID_LOGIN_CREDENTIALS') !== false) {
                $_SESSION['login_error'] = "Invalid email or password.";
            } else {
                $_SESSION['login_error'] = "Login failed. Please try again.";
            }
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
