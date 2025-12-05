<?php
// capture-order.php
session_start();
require_once __DIR__ . '/../app/db.php';

$paypal_order_id = $_GET['token'] ?? $_POST['token'] ?? null;
if (!$paypal_order_id) {
    header("Location: /Project-CSOFT/project-root/payment_failed.php");
    exit;
}

// PayPal credentials
$clientId = "AeMhb8ACVoP..."; // same as create-order
$secret   = "EL0f-NdTLI...";

// Step 1: get access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
$response = curl_exec($ch);
$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? null;
curl_close($ch);

if (!$accessToken) {
    header("Location: /Project-CSOFT/project-root/payment_failed.php");
    exit;
}

// Step 2: capture payment
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$paypal_order_id}/capture");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
]);
$result = curl_exec($ch);
$data = json_decode($result, true);
curl_close($ch);

if (isset($data['status']) && strtoupper($data['status']) === 'COMPLETED') {
    // find our order_transactions row by paypal_order_id
    $stmt = $pdo->prepare("SELECT * FROM order_transactions WHERE paypal_order_id = ? LIMIT 1");
    $stmt->execute([$paypal_order_id]);
    $txn = $stmt->fetch(PDO::FETCH_ASSOC);

    // If we inserted earlier, txn should exist. If not, we try to retrieve custom_id from purchase_units
    $our_order_id = null;
    if ($txn) {
        $our_order_id = $txn['order_id'];
        // update transaction
        $stmt = $pdo->prepare("
            UPDATE order_transactions 
            SET status = 'completed', paypal_transaction_id = ?, response_json = ?, updated_at = NOW()
            WHERE paypal_order_id = ?
        ");
        $paypal_txn_id = $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
        $stmt->execute([$paypal_txn_id, json_encode($data), $paypal_order_id]);
    } else {
        // fallback: try get custom_id
        $our_order_id = $data['purchase_units'][0]['custom_id'] ?? null;
        // Insert transaction if needed
        $stmt = $pdo->prepare("
            INSERT INTO order_transactions (order_id, user_id, paypal_order_id, amount, currency_code, status, response_json)
            VALUES (?, ?, ?, ?, ?, 'completed', ?)
        ");
        // user_id: fetch from orders table if available
        $user_id = null;
        if ($our_order_id) {
            $s = $pdo->prepare("SELECT user_id FROM orders WHERE id = ? LIMIT 1");
            $s->execute([$our_order_id]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            $user_id = $row['user_id'] ?? null;
        }
        $amount = $data['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0;
        $currency = $data['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] ?? 'USD';
        $stmt->execute([$our_order_id, $user_id, $paypal_order_id, $amount, json_encode($data)]);
    }

    // mark order paid & clear cart if we have our_order_id
    if ($our_order_id) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Paid' WHERE id = ?");
        $stmt->execute([$our_order_id]);

        // clear user's cart
        if (!empty($user_id)) {
            $del = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $del->execute([$user_id]);
        } else {
            // fallback get user_id from orders
            $s = $pdo->prepare("SELECT user_id FROM orders WHERE id = ? LIMIT 1");
            $s->execute([$our_order_id]);
            $r = $s->fetch(PDO::FETCH_ASSOC);
            if ($r && $r['user_id']) {
                $del = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $del->execute([$r['user_id']]);
            }
        }

        // redirect to our order success page with our order id
        header("Location: /Project-CSOFT/project-root/public/order_success.php?order_id={$our_order_id}");
        exit;
    } else {
        header("Location: /Project-CSOFT/project-root/payment_failed.php");
        exit;
    }
} else {
    // capture failed
    header("Location: /Project-CSOFT/project-root/payment_failed.php");
    exit;
}
