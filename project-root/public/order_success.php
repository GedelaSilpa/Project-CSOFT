<?php
// order_success.php
session_start();
require_once __DIR__ . '/../app/db.php';

$order_id = $_GET['order_id'] ?? $_SESSION['pending_order_id'] ?? null;
if (!$order_id) {
    header("Location: index.php");
    exit;
}

// fetch order details
$stmt = $pdo->prepare("SELECT o.*, u.name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

$itemStmt = $pdo->prepare("
    SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?
");
$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// clear pending in session
unset($_SESSION['pending_order_id']);
unset($_SESSION['cart']); // guest session cart if any

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Order Placed</title>
<style>
body {font-family:'Poppins',sans-serif;background:#f5f4fa;padding:60px 20px;}
.card{max-width:700px;margin:0 auto;background:#fff;padding:30px;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.08);text-align:center;}
.success{color:#299d43;font-size:26px;margin-bottom:10px;}
.items{text-align:left;margin-top:20px;}
.items li{padding:8px 0;border-bottom:1px solid #eee;}
</style>
</head>
<body>
<div class="card">
    <div class="success">🎉 Order Placed Successfully!</div>
    <p>Your order ID: <strong>#<?= htmlspecialchars($order_id) ?></strong></p>
    <p>Amount: <strong>₹<?= number_format($order['total_amount'],2) ?></strong></p>

    <div class="items">
        <h4>🛒 Items</h4>
        <ul>
        <?php foreach ($items as $it): ?>
            <li><?= htmlspecialchars($it['name']) ?> — <?= (int)$it['quantity'] ?> × ₹<?= number_format($it['price'],2) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>

    <a href="products.php" style="display:inline-block;margin-top:20px;background:linear-gradient(45deg,#6a1b9a,#8e24aa);color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;">Continue Shopping</a>
</div>
</body>
</html>
