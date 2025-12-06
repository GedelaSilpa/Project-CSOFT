<?php

// -----------------------------
// Load .env manually
// -----------------------------
// -----------------------------
// Custom env() function
// -----------------------------
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}


// -----------------------------
// Load DB Credentials
// -----------------------------
$host = env('DB_HOST');
$db   = env('DB_DATABASE');
$user = env('DB_USERNAME');
$pass = env('DB_PASSWORD');
$port = env('DB_PORT');
$charset = 'utf8mb4';


// -----------------------------
// PayPal Sandbox Credentials
// -----------------------------
$config = [
    'paypal' => [
        'client_id' => "AeMhb8ACVoPBMrfxnmZhUap9eIOAppJe71IAqOvxDuiuXMQwuWk_FUHCQOlCGmDsH9yt0-372L0axs0r",
        'secret'    => "EL0f-NdTLIsXP8ZcVJIrPJhwew9ec9WrIA3eiTGHvOAieoeR7O-4SMlXjE8W2Y2CAjBEv5s0bbKW4GTA",
        'base_url'  => "https://api-m.sandbox.paypal.com"
    ]
];


// -----------------------------
// Create DB connection
// -----------------------------
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("❌ DB Connection failed: " . $e->getMessage());
}
