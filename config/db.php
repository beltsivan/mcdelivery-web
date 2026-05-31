<?php
// Firebase connection (replaces MySQL)
// 1. Install Composer: https://getcomposer.org/download/
// 2. Run: composer require kreait/firebase-php
// 3. Download service-account.json from Firebase Console > Project Settings > Service Accounts > Generate New Private Key
// 4. Place the JSON file in this directory as 'firebase-credentials.json'

$firebaseInitialized = false;

// Legacy variable — kept so cart function signatures (which accept $conn as first param) still work
$conn = null;

// Web API Key from Firebase Console > Project Settings > General
// Used for Firebase Auth REST API (sign-in verification)
$firebaseApiKey = 'AIzaSyAKvNq4D2cJQOKhiIZ22mICuadOoSB7w0k'; // REPLACE WITH YOUR KEY

$firebaseCredentialsPath = __DIR__ . '/firebase-credentials.json';

if (!file_exists($firebaseCredentialsPath) || !file_exists(__DIR__ . '/../vendor/autoload.php')) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Firebase not configured.\n\n";
    if (!file_exists($firebaseCredentialsPath)) {
        echo "- Missing: config/firebase-credentials.json\n";
        echo "  Download from Firebase Console > Project Settings > Service Accounts > Generate New Private Key\n\n";
    }
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        echo "- Missing: vendor/ folder\n";
        echo "  Run: composer require kreait/firebase-php\n\n";
    }
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;
use Kreait\Firebase\Factory;

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
    $auth = $factory->createAuth();
    $firebaseInitialized = true;
} catch (\Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Firebase initialization failed: " . $e->getMessage() . "\n";
    exit;
}
