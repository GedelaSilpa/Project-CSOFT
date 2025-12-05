<?php
header("Content-Type: application/json");
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

session_start();

// PayPal sandbox credentials
$clientId = "AeMhb8ACVoPBMrfxnmZhUap9eIOAppJe71IAqOvxDuiuXMQwuWk_FUHCQOlCGmDsH9yt0-372L0axs0r";
$secret = "EL0f-NdTLIsXP8ZcVJIrPJhwew9ec9WrIA3eiTGHvOAieoeR7O-4SMlXjE8W2Y2CAjBEv5s0bbKW4GTA";

// Optional: INR -> USD conversion rate (replace with actual rate)
$exchangeRate = 0.012; // Example: 1 INR = 0.012 USD

// Handle JSON POST input
$input = json_decode(file_get_contents('php://input'), true);
$order_id = $_SESSION['pending_order_id'] ?? ($input['order_id'] ?? null);

if (!$order_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing order_id']));
}

// Fetch order and user info
$stmt = $pdo->prepare("
    SELECT o.id, o.user_id, u.name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit(json_encode(['error' => 'Order not found']));
}

// Fetch order items
$itemStmt = $pdo->prepare("
    SELECT oi.product_id, p.name AS product_name, oi.quantity, oi.price, (oi.quantity * oi.price) AS subtotal
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$order_id]);
$orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$orderItems) {
    http_response_code(400);
    exit(json_encode(['error' => 'No items found for this order']));
}

// Calculate total USD amount
$totalAmountUSD = 0;
foreach ($orderItems as $item) {
    $totalAmountUSD += $item['subtotal'] * $exchangeRate;
}
$totalAmountUSD = number_format($totalAmountUSD, 2, '.', '');

// --- Step 1: Get PayPal Access Token ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($tokenResponse, true);
if (!isset($tokenData['access_token'])) {
    http_response_code(500);
    exit(json_encode(['error' => 'Failed to get PayPal access token', 'response' => $tokenResponse]));
}
$token = $tokenData['access_token'];

// --- Step 2: Create PayPal Order ---
$purchaseItems = array_map(function ($item) use ($exchangeRate) {
    return [
        "name" => $item['product_name'],
        "unit_amount" => [
            "currency_code" => "USD",
            "value" => number_format($item['price'] * $exchangeRate, 2, '.', '')
        ],
        "quantity" => (string) $item['quantity']
    ];
}, $orderItems);

$data = [
    "intent" => "CAPTURE",
    "purchase_units" => [
        [
            "amount" => [
                "currency_code" => "USD",
                "value" => $totalAmountUSD,
                "breakdown" => [
                    "item_total" => [
                        "currency_code" => "USD",
                        "value" => $totalAmountUSD
                    ]
                ]
            ],
            "items" => $purchaseItems
        ]
    ],
    "application_context" => [
        "return_url" => "http://localhost/Project-CSOFT/project-root/public/paypalsuccess.php",
        "cancel_url" => "http://localhost/Project-CSOFT/project-root/public/paypalcancel.php"
    ]
];

$ch = curl_init("https://api-m.sandbox.paypal.com/v2/checkout/orders");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$orderResponse = curl_exec($ch);
curl_close($ch);

$responseArr = json_decode($orderResponse, true);
$paypal_order_id = $responseArr['id'] ?? null;

if (!$paypal_order_id) {
    http_response_code(500);
    exit(json_encode(['error' => 'Failed to create PayPal order', 'paypal_response' => $responseArr]));
}

// --- Step 3: Insert transaction into DB ---
$stmt = $pdo->prepare("
    INSERT INTO order_transactions 
    (order_id, user_id, paypal_order_id, amount, currency_code, status, response_json)
    VALUES (?, ?, ?, ?, 'USD', 'created', ?)
");
$stmt->execute([
    $order_id,
    $order['user_id'],
    $paypal_order_id,
    $totalAmountUSD,
    json_encode($responseArr)
]);

// --- Step 4: Return JSON response ---
header('Content-Type: application/json');
echo json_encode([
    "paypal_order_id" => $paypal_order_id,
    "usd_amount" => $totalAmountUSD,
    "paypal_response" => $responseArr
]);
