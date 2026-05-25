<?php
session_start();

if (isset($_SESSION['Staff_Id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

include('config/db.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$firebaseInitialized) {
        $error = 'Firebase not configured.';
    } else {
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
                $verifiedToken = $auth->verifyIdToken($data['idToken']);
                $uid = $verifiedToken->claims()->get('sub');

                // Read staff data from Firestore
                $db = $firestore->database();
                $staffDoc = $db->collection('staff')->document($uid)->snapshot();

                if ($staffDoc->exists()) {
                    $staff = $staffDoc->data();
                    $_SESSION['Staff_Id'] = $uid;
                    $_SESSION['Staff_FName'] = $staff['Staff_FName'] ?? '';
                    $_SESSION['Staff_LName'] = $staff['Staff_LName'] ?? '';
                    $_SESSION['Staff_Role'] = $staff['Staff_Role'] ?? 'Staff';
                    $_SESSION['Staff_Email'] = $staff['Staff_Email'] ?? $email;
                    $_SESSION['Staff_Brnch_Id'] = $staff['Staff_Brnch_Id'] ?? null;

                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    $error = 'Staff account not found.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (\Exception $e) {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - McDelivery Admin</title>
    <link rel="stylesheet" href="css/admin/admin_style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #292929;
            font-family: 'Arial', sans-serif;
            margin: 0;
        }
        .login-card {
            background: #fff;
            border-radius: 18px;
            padding: 48px 36px;
            width: 380px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
            text-align: center;
        }
        .login-card h1 {
            color: #292929;
            font-size: 24px;
            margin: 0 0 6px;
        }
        .login-card .subtitle {
            color: #777;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .login-card input {
            width: 100%;
            padding: 13px 14px;
            margin-bottom: 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .login-card input:focus {
            outline: 2px solid #FFBC0D;
            border-color: #FFBC0D;
        }
        .login-card button {
            width: 100%;
            background: #FFBC0D;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            color: #292929;
        }
        .login-card button:hover {
            background: #e5a90b;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .login-card .admin-badge {
            display: inline-block;
            background: #292929;
            color: #FFBC0D;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 16px;
        }
        .login-card .back-link {
            display: block;
            margin-top: 18px;
            color: #888;
            font-size: 13px;
            text-decoration: none;
        }
        .login-card .back-link:hover {
            color: #292929;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="admin-badge">STAFF LOGIN</div>
        <h1>Welcome</h1>
        <p class="subtitle">Sign in to manage McDelivery</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Log In</button>
        </form>

        <a href="index.php" class="back-link">&larr; Back to Customer Site</a>
    </div>
</body>
</html>
