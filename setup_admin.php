<?php
/**
 * Super Admin Setup Script
 * Creates the initial admin account for the McDelivery admin dashboard.
 *
 * Usage: C:\xampp\php\php.exe setup_admin.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;
use Kreait\Firebase\Factory;

$firebaseCredentialsPath = __DIR__ . '/config/firebase-credentials.json';

if (!file_exists($firebaseCredentialsPath)) {
    die("Missing: config/firebase-credentials.json\n");
}

echo "=== McDelivery Admin Setup ===\n\n";

// Initialize Firebase
echo "Initializing Firebase... ";
try {
    $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
    $credentials = new ServiceAccountCredentials($scopes, $firebaseCredentialsPath);

    $factory = (new Factory)
        ->withServiceAccount($firebaseCredentialsPath)
        ->withFirestoreClientConfig([
            'transport' => 'rest',
            'credentials' => $credentials,
        ]);

    $firestore = $factory->createFirestore();
    $db = $firestore->database();
    $auth = $factory->createAuth();
    echo "OK\n\n";
} catch (Exception $e) {
    die("FAILED: " . $e->getMessage() . "\n");
}

// Admin credentials
$adminEmail = 'admin@gmail.com';
$adminPassword = 'admin123';
$adminFName = 'System';
$adminLName = 'Admin';

echo "Creating super admin account...\n";

// Step 1: Check if already exists
try {
    $existingUser = $auth->getUserByEmail($adminEmail);
    echo "  Admin account already exists in Auth (UID: {$existingUser->uid})\n";
    $uid = $existingUser->uid;
} catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
    // Create admin user in Firebase Auth
    $user = $auth->createUserWithEmailAndPassword($adminEmail, $adminPassword);
    $uid = $user->uid;
    echo "  Created Firebase Auth user: $uid\n";
}

// Step 2: Create/update staff document
$staffDoc = $db->collection('staff')->document($uid);
$staffDoc->set([
    'Staff_Brnch_Id' => null,
    'Staff_Role' => 'System Admin',
    'Staff_FName' => $adminFName,
    'Staff_LName' => $adminLName,
    'Staff_Phone' => null,
    'Staff_Email' => $adminEmail,
]);

echo "  Staff document created/updated with role: System Admin\n";

echo "\n=== Setup Complete! ===\n";
echo "Super Admin Credentials:\n";
echo "  Email:    $adminEmail\n";
echo "  Password: $adminPassword\n\n";
echo "Login at: http://localhost/mcdelivery-web/staff_login.php\n";
echo "Then you can manage branches, managers, products, and orders.\n";
