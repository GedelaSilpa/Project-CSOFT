<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

/*
|--------------------------------------------------------------------------
| 1️⃣ Ensure session cart exists
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];  
}

/*
|--------------------------------------------------------------------------
| 2️⃣ Handle Quantity Update (SESSION FIRST)
|--------------------------------------------------------------------------
*/
if (isset($_POST['update_qty'])) {
    $item_id = $_POST['item_id'];
    $quantity = max(1, (int)$_POST['quantity']);

    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] = $quantity;
    }

    // If logged in → sync to DB
    if ($user_id) {
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $item_id, $user_id]);
    }

    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| 3️⃣ Remove item
|--------------------------------------------------------------------------
*/
if (isset($_GET['remove'])) {
    $item_id = $_GET['remove'];

    unset($_SESSION['cart'][$item_id]);

    if ($user_id) {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
    }

    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| 4️⃣ Fetch Items (SESSION + DB)
|--------------------------------------------------------------------------
*/
$cart_items = [];

if ($user_id) {
    // Fetch from DB for logged-in users
    $stmt = $pdo->prepare("SELECT ci.*, p.name, p.price, p.image 
                           FROM cart_items ci
                           JOIN products p ON ci.product_id = p.id
                           WHERE ci.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // Guest user → session-based cart
    require_once __DIR__ . '/../app/db.php';

    foreach ($_SESSION['cart'] as $id => $item) {
        $stmt = $pdo->prepare("SELECT id AS product_id, name, price, image 
                               FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);

        $cart_items[] = [
            'id'        => $id,
            'product_id'=> $p['product_id'],
            'name'      => $p['name'],
            'price'     => $p['price'],
            'image'     => $p['image'],
            'quantity'  => $item['quantity']
        ];
    }
}

/*
|--------------------------------------------------------------------------
| 5️⃣ Calculate Total
|--------------------------------------------------------------------------
*/
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f5f4fa;
    margin: 0;
}
.container {
    max-width: 900px;
    margin: 60px auto;
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    color: #6a1b9a;
    font-weight: 600;
    margin-bottom: 25px;
}
table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
}
thead {
    background: linear-gradient(45deg, #6a1b9a, #8e24aa);
    color: white;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}
img {
    width: 70px;
    border-radius: 10px;
}
.qty-controls {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.qty-btn {
    background: #6a1b9a;
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    font-size: 18px;
    cursor: pointer;
    transition: 0.3s;
}
.qty-btn:hover {
    background: #4a0072;
}
.btn-delete {
    background: #e53935;
    color: #fff;
    padding: 8px 14px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
}
.btn-delete:hover {
    background: #c62828;
}
.btn-success {
    display: inline-block;
    background: linear-gradient(45deg, #43a047, #66bb6a);
    color: #fff;
    padding: 12px 28px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
}
.btn-success:hover {
    background: linear-gradient(45deg, #2e7d32, #43a047);
}
h3 {
    text-align: right;
    color: #333;
}
.empty {
    text-align: center;
    color: #777;
    font-size: 1.2rem;
}
</style>

<div class="container">
    <h1>Your Cart</h1>

    <?php if (empty($cart_items)): ?>
        <p class="empty">Your cart is empty.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($cart_items as $item): ?>
            <tr>
                <td><img src="<?= htmlspecialchars($item['image']) ?>"></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td>₹<?= number_format($item['price'], 2) ?></td>

                <td>
                    <form method="post" class="qty-controls">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <input type="hidden" name="update_qty" value="1">

                        <button type="submit" name="quantity" value="<?= $item['quantity'] - 1 ?>" class="qty-btn">−</button>
                        <span><?= $item['quantity'] ?></span>
                        <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>" class="qty-btn">+</button>
                    </form>
                </td>

                <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>

                <td><a href="?remove=<?= $item['id'] ?>" class="btn-delete">🗑 Remove</a></td>
            </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

        <h3>Total: ₹<?= number_format($total, 2) ?></h3>

        <div style="text-align:right;">
            <a href="checkout.php" class="btn-success">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>
