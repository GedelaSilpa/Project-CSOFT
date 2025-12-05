<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_auth('user');
if (session_status() == PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;

if (!$user_id || !$product_id) {
    echo json_encode(['status' => 'error']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM product_notifications WHERE user_id=? AND product_id=?");
$stmt->execute([$user_id, $product_id]);
$exists = $stmt->fetchColumn();

if (!$exists) {
    $pdo->prepare("INSERT INTO product_notifications (user_id, product_id) VALUES (?, ?)")->execute([$user_id, $product_id]);
}

echo json_encode(['status' => 'success']);
