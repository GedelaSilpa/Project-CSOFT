<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_auth('user');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$user_id || !$product_id || !$action) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

// ✅ 1. Fetch product details (especially stock)
$stmt = $pdo->prepare("SELECT id, name, stock, price, is_active FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}

if ($product['is_active'] !== 'Y') {
    echo json_encode(['status' => 'error', 'message' => 'This product is inactive']);
    exit;
}

// ✅ 2. Check stock availability
$available = (int)$product['stock'];
if ($available <= 0) {
    echo json_encode(['status' => 'limit', 'message' => 'Only 0 items are available in stock!']);
    exit;
}

// ✅ 3. Check if product already exists in cart
$stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ 4. Handle each cart action properly
switch ($action) {
    case 'add':
        if ($cartItem) {
            // ✅ Friendly limit message
            if ($cartItem['quantity'] >= $available) {
                echo json_encode([
                    'status' => 'limit',
                    'message' => "We only have {$available} pieces left at this moment."
                ]);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?");
            $stmt->execute([$cartItem['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$user_id, $product_id]);
        }
        break;

    case 'increase':
        if ($cartItem) {
            // ✅ Friendly limit message
            if ($cartItem['quantity'] >= $available) {
                echo json_encode([
                    'status' => 'limit',
                    'message' => "We only have {$available} pieces left at this moment."
                ]);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?");
            $stmt->execute([$cartItem['id']]);
        }
        break;

    case 'decrease':
        if ($cartItem) {
            if ($cartItem['quantity'] > 1) {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity - 1 WHERE id = ?");
                $stmt->execute([$cartItem['id']]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
                $stmt->execute([$cartItem['id']]);
            }
        }
        break;

    case 'remove':
        if ($cartItem) {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
            $stmt->execute([$cartItem['id']]);
        }
        break;
}

// ✅ 5. Respond with success
echo json_encode(['status' => 'success']);
exit;
