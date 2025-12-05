<?php
ob_start();
session_start();
require_once __DIR__ . '/../app/auth.php';

// Check user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user cart items
$stmt = $pdo->prepare("
    SELECT 
        p.id AS product_id,
        p.name, 
        p.price, 
        c.quantity
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total amount
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    $door_no = trim($_POST['door_no'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    // Combine all into a single string for DB
    $address = "$door_no, $street, $landmark, $city, $state, $country - $pincode";

    $user_id = $_SESSION['user_id'];

    if (empty($address)) {
        $error = "Please enter your delivery address.";
    } else {

        if (empty($cart_items)) {
            $error = "Your cart is empty.";
        } else {

            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }

            // Check if pending order exists
            $stmt = $pdo->prepare("
                SELECT id FROM orders
                WHERE user_id = ? AND status = 'pending'
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $existing_order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_order) {
                // Update existing pending order
                $order_id = $existing_order['id'];

                $stmt = $pdo->prepare("
                    UPDATE orders
                    SET address = ?, total_amount = ?, order_date = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$address, $total_amount, $order_id]);

                // Update/Insert order_items
                foreach ($cart_items as $item) {
                    $stmt = $pdo->prepare("
                        SELECT id FROM order_items
                        WHERE order_id = ? AND product_id = ?
                    ");
                    $stmt->execute([$order_id, $item['product_id']]);
                    $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing_item) {
                        $stmt = $pdo->prepare("
                            UPDATE order_items 
                            SET quantity = ?, price = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $item['quantity'],
                            $item['price'],
                            $existing_item['id']
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO order_items (order_id, product_id, quantity, price)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $order_id,
                            $item['product_id'],
                            $item['quantity'],
                            $item['price']
                        ]);
                    }
                }

            } else {
                // Create new order
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, address, total_amount, status, order_date)
                    VALUES (?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$user_id, $address, $total_amount]);

                $order_id = $pdo->lastInsertId();

                // Insert order_items
                foreach ($cart_items as $item) {
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price']
                    ]);
                }
            }
            // Redirect to payment selection page
            $_SESSION['pending_order_id'] = $order_id;
            header("Location: paymentpage.php");
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | CSOFT Healthcare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f7f8fa;
            margin: 0;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #6a1b9a;
            text-align: center;
            margin-bottom: 25px;
        }

        .section {
            margin-bottom: 30px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: none;
        }

        .order-summary {
            background: #f5f4fa;
            border-radius: 10px;
            padding: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .total {
            font-weight: bold;
            color: #4caf50;
        }

        .btn-place {
            display: block;
            width: 100%;
            background: linear-gradient(45deg, #6a1b9a, #8e24aa);
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-place:hover {
            background: linear-gradient(45deg, #4a0072, #6a1b9a);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        input[type=text] {
            width: 98%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        input[type=text]:focus {
            border-color: #6a1b9a;
            outline: none;
            box-shadow: 0 0 5px rgba(106, 27, 154, 0.3);
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Checkout</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <!-- Delivery Address Section -->
            <div class="section"
                style="margin-top:20px; background: rgba(0, 0, 0, 0.03); border-radius: 10px; padding: 20px;">
                <label style="font-weight:600; display:flex; align-items:center; gap:8px; font-size:16px;">
                    <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" alt="Location Icon"
                        style="width:20px; height:20px;">
                    Shipping Address
                </label>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:10px;">
                    <input type="text" name="door_no" placeholder="Door No" required>
                    <input type="text" name="street" placeholder="Street" required>
                </div>

                <input type="text" name="landmark" placeholder="Nearby / Landmark" style="margin-top:10px;" required>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:10px;">
                    <input type="text" name="city" placeholder="City" required>
                    <input type="text" name="state" placeholder="State" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:10px;">
                    <input type="text" name="country" placeholder="Country" required>
                    <input type="text" name="pincode" placeholder="Pincode" required>
                </div>
            </div>


            <!-- Order Summary -->
            <div class="section order-summary"
                style="background: rgba(0, 0, 0, 0.03); border-radius: 10px; padding: 20px;">
                <h3>Bill Summary</h3>

                <?php if (!empty($cart_items)): ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <span><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                            <span>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No items in your cart.</p>
                <?php endif; ?>

                <hr>

                <div class="order-item total">
                    <span>Total Amount:</span>
                    <span>₹<?= number_format($total, 2) ?></span>
                </div>
            </div>

            <?php if ($total > 0): ?>
                <button type="submit" name="place_order" class="btn-place">Proceed to Payment</button>
            <?php endif; ?>

        </form>
    </div>

</body>

</html>