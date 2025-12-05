<?php
// place_order.php
session_start();
require_once __DIR__ . '/../app/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Basic validation - adjust as needed
$address = trim($_POST['address'] ?? '');
$payment_mode = $_POST['payment_mode'] ?? '';

if (empty($address) || empty($payment_mode)) {
    $_SESSION['checkout_error'] = "Please fill all required fields.";
    header("Location: checkout.php");
    exit;
}

// Fetch cart items (from DB)
$stmt = $pdo->prepare("
    SELECT 
        ci.id AS cart_id, ci.product_id, ci.quantity, p.price
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    $_SESSION['checkout_error'] = "Your cart is empty.";
    header("Location: cart.php");
    exit;
}

// calculate total
$total_amount = 0;
foreach ($cart_items as $c) {
    $total_amount += ($c['price'] * $c['quantity']);
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert into orders
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, address, payment_mode, total_amount, status, order_date)
        VALUES (?, ?, ?, ?, 'Pending', NOW())
    ");
    $stmt->execute([$user_id, $address, $payment_mode, $total_amount]);
    $order_id = $pdo->lastInsertId();

    // Insert order_items
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($cart_items as $c) {
        $stmtItem->execute([$order_id, $c['product_id'], $c['quantity'], $c['price']]);
    }

    // Commit
    $pdo->commit();

    // Save pending order id in session for payment flow
    $_SESSION['pending_order_id'] = $order_id;

    // If payment mode is not online (e.g., COD), finalize accordingly:
    if ($payment_mode === 'COD') {
        // For COD, mark Paid? No — keep Pending, but show success or confirmation message.
        header("Location: order_success.php?order_id={$order_id}");
        exit;
    } else if ($payment_mode === 'PayPal') {
        // redirect to payment page for PayPal (the payment page will call create-order.php)
        header("Location: payment.php?order_id={$order_id}");
        exit;
    } else {
        // For other payment modes (UPI, Bank, Card), go to payment page where user sees instructions
        header("Location: payment.php?order_id={$order_id}");
        exit;
    }

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Place order error: " . $e->getMessage());
    $_SESSION['checkout_error'] = "Unable to create order. Try again.";
    header("Location: checkout.php");
    exit;
}
