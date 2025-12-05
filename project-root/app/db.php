<?php

// -----------------------------
// Load .env manually
// -----------------------------

$envPath = dirname(__DIR__) . '/.env';   // Correct path: project-root/.env

if (file_exists($envPath)) {

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {

        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}


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
