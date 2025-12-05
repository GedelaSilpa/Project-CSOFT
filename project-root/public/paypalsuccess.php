<?php
session_start();
require_once __DIR__ . '/../app/db.php';

$paypal = $config['paypal'];

$paypal_order_id = $_GET['token'] ?? null;

if (!$paypal_order_id) {
    header("Location: order_failed.php?reason=missing_token");
    exit;
}

$order_id = $_SESSION['pending_order_id'] ?? null;
$user_id  = $_SESSION['user_id'] ?? null;

if (!$order_id) {
    header("Location: order_failed.php?reason=session_expired");
    exit;
}

/* STEP 1: ACCESS TOKEN */
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $paypal['base_url'] . "/v1/oauth2/token",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $paypal['client_id'] . ":" . $paypal['secret'],
    CURLOPT_POSTFIELDS => "grant_type=client_credentials"
]);
$response = curl_exec($ch);
$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? null;
curl_close($ch);

if (!$accessToken) {
    header("Location: order_failed.php?reason=token_failed");
    exit;
}

/* STEP 2: CAPTURE PAYMENT */
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $paypal['base_url'] . "/v2/checkout/orders/$paypal_order_id/capture",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    ],
    CURLOPT_POST => true
]);
$response = curl_exec($ch);
curl_close($ch);

$paymentData = json_decode($response, true);

$paypal_transaction_id = $paymentData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
$status = $paymentData['status'] ?? 'FAILED';

/* STEP 3: UPDATE order_transactions */
$stmt = $pdo->prepare("
    UPDATE order_transactions 
    SET 
        status = ?, 
        paypal_transaction_id = ?, 
        response_json = ?, 
        updated_at = NOW()
    WHERE paypal_order_id = ?
");
$stmt->execute([
    $status,
    $paypal_transaction_id,
    json_encode($paymentData),
    $paypal_order_id
]);

/* STEP 4: SUCCESSFUL PAYMENT */
if ($status === 'COMPLETED') {

    $updateOrder = $pdo->prepare("
        UPDATE orders 
        SET 
            status = 'Paid',
            payment_mode = 'PayPal',
            payment_status = 'paid'
        WHERE id = ?
    ");
    $updateOrder->execute([$order_id]);

    if ($user_id) {
        $clear = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $clear->execute([$user_id]);
    }

    header("Location: order_success.php?order_id=" . $order_id);
    exit;
}

/* FAILURE */
header("Location: order_failed.php?reason=paypal_failed");
exit;
