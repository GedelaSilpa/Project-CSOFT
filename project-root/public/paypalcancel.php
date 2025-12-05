<?php
session_start();
require_once __DIR__ . '/../app/db.php';

$paypal_order_id = $_GET['token'] ?? null;
$order_id = $_SESSION['pending_order_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

/* If no PayPal token → simple redirect */
if (!$paypal_order_id) {
    header("Location: order_failed.php?reason=missing_token");
    exit;
}

/* STEP 1: Mark transaction as cancelled */
$stmt = $pdo->prepare("
    UPDATE order_transactions 
    SET status = 'failed', updated_at = NOW()
    WHERE paypal_order_id = ?
");
$stmt->execute([$paypal_order_id]);

/* STEP 2: Mark order as failed (optional but recommended) */
if ($order_id) {
    $updateOrder = $pdo->prepare("
        UPDATE orders 
        SET payment_status = 'failed', status = 'Pending'
        WHERE id = ?
    ");
    $updateOrder->execute([$order_id]);
}

/* STEP 3: Restore / keep cart items */
# (Nothing to remove, because payment did not complete)

/* STEP 4: Redirect to a clean cancel page */
header("Location: order_failed.php?reason=payment_cancelled");
exit;

