<?php
ob_start();
session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch active products
$products = $pdo->query("SELECT * FROM products WHERE is_active='Y' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
// Get total cart item count (for badge)
$cartCount = 0;
$stmt = $pdo->prepare("SELECT SUM(quantity) AS total FROM cart_items WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartCount = $stmt->fetchColumn() ?: 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSOFT HEALTHCARE SOLUTIONS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />

<style>
body {
    font-family: "Poppins", sans-serif;
    background: #f8f9fc;
    margin: 0;
    padding: 20px;
    color: #333;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
    max-width: 1300px;
    margin: 40px auto;
    padding: 10px;
}

.product-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    text-align: center;
    transition: all 0.3s ease;
    padding: 15px;
    box-sizing: border-box;
}
.product-card:hover { transform: translateY(-3px); }
.product-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-bottom: 1px solid #eee;
    transition: transform 0.3s ease;
}
.product-card:hover img { transform: scale(1.05); }
.product-card h3 { font-size: 1.1rem; margin: 12px 0 4px; color: #222; }
.product-card p { margin: 5px 0; font-size: 0.9rem; color: #666; }
.product-card p strong { color: #2b8a3e; font-weight: 600; display: block; }

.cart-action { margin-top: 12px; }

/* Base Cart Button Reset */
.cart-btn {
    all: unset;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-sizing: border-box;
    font-family: inherit;
}

/* Add to Cart Button */
.cart-btn-add {
    background: linear-gradient(135deg, #28a745, #218838);
    color: white;
    padding: 8px 15px;
    border-radius: 25px;
    font-weight: 600;
    text-transform: uppercase;
    transition: 0.3s;
}
.cart-btn-add:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
}

/* Quantity Container */
.quantity-box {
    display: inline-flex;
    align-items: center;
    background: #eef2ff;
    border-radius: 30px;
    padding: 5px 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* Increase/Decrease/Remove Buttons */
.cart-btn-increase,
.cart-btn-decrease,
.cart-btn-remove {
    background: #007bff;
    color: white;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    font-size: 14px;
    font-weight: bold;
    transition: background 0.2s;
}
.cart-btn-increase:hover,
.cart-btn-decrease:hover,
.cart-btn-remove:hover {
    background: #0056d2;
}

.quantity-box .qty {
    font-weight: 600;
    font-size: 15px;
    margin: 0 8px;
    color: #333;
    min-width: 20px;
    text-align: center;
}

/* Responsive */
@media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px) { .product-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .product-grid { grid-template-columns: repeat(1, 1fr); } }
</style>
</head>
<body>

<!-- Navigation -->
<nav>
    <a href="index.php" class="logo-wrapper">
        <img src="assets/images/Csoft_logo.png" alt="Company Logo" class="logo-img">
    </a>
    <ul>
        <li><a href="home.php#home">Home</a></li>
        <li><a href="products.php">Products</a></li>
        <li><a href="cart.php">Cart</a></li>
        <li class="user-dropdown">
            <button><?= htmlspecialchars($_SESSION['user_name']); ?></button>
            <div class="user-dropdown-content">
                <a href="logout.php">Sign Out</a>
            </div>
        </li>
    </ul>
</nav>

<!-- ✅ Products -->
<div class="main-wrapper">
  <div class="product-grid">
    <?php foreach ($products as $p): ?>
      <div class="product-card">
        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p><?= htmlspecialchars($p['category']) ?></p>
        <p><strong>₹<?= number_format($p['price'], 2) ?></strong></p>

        <?php if ($p['stock'] <= 0): ?>
          <span class="out-of-stock">Out of Stock</span>
          <label class="notify-label">
            <input type="checkbox" class="notify-me" data-id="<?= $p['id'] ?>"> Notify me when available
          </label>
        <?php else: ?>
          <?php
          $stmtCart = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
          $stmtCart->execute([$_SESSION['user_id'], $p['id']]);
          $cartItem = $stmtCart->fetch(PDO::FETCH_ASSOC);
          ?>
          <div class="cart-action" data-product-id="<?= $p['id'] ?>">
            <?php if ($cartItem): ?>
              <div class="quantity-box">
                <?php if ($cartItem['quantity'] == 1): ?>
                  <button class="cart-btn cart-btn-remove" data-id="<?= $p['id'] ?>">🗑</button>
                  <span class="qty"><?= $cartItem['quantity'] ?></span>
                  <button class="cart-btn cart-btn-increase" data-id="<?= $p['id'] ?>">➕</button>
                <?php else: ?>
                  <button class="cart-btn cart-btn-decrease" data-id="<?= $p['id'] ?>">➖</button>
                  <span class="qty"><?= $cartItem['quantity'] ?></span>
                  <button class="cart-btn cart-btn-increase" data-id="<?= $p['id'] ?>">➕</button>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <button class="cart-btn cart-btn-add" data-id="<?= $p['id'] ?>">Add to Cart</button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<footer>
    <p>&copy; 2025 CSOFT HEALTHCARE SOLUTIONS</p>
</footer>

<script>
document.addEventListener("click", async function(e) {
    if (!e.target.closest(".cart-btn-add, .cart-btn-increase, .cart-btn-decrease, .cart-btn-remove")) return;

    const target = e.target.closest("button");
    const productId = target.dataset.id;
    let action = "add";
    if (target.classList.contains("cart-btn-increase")) action = "increase";
    else if (target.classList.contains("cart-btn-decrease")) action = "decrease";
    else if (target.classList.contains("cart-btn-remove")) action = "remove";

    const res = await fetch("update_cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${productId}&action=${action}`
    });
    const data = await res.json();

 // ✅ Improved handling
  if (data.status === "limit") {
      alert(data.message || "We only have limited stock available.");
      return;
  }

  if (data.status === "error") {
      alert(data.message || "Something went wrong, please try again.");
      return;
  }

  if (data.status === "success") {
      location.reload();
  }
});

// ✅ "Notify Me" feature
document.addEventListener("change", async function(e) {
  if (e.target.classList.contains("notify-me")) {
    const productId = e.target.dataset.id;
    const res = await fetch("notify_me.php", {
      method: "POST",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: `product_id=${productId}`
    });
    const data = await res.json();
    if (data.status === "success") {
      alert("We'll notify you when this product is restocked!");
    }
  }
});

function updateCartUI(box, action) {
    let qtyBox = box.querySelector(".quantity-box");
    let addBtn = box.querySelector(".cart-btn-add");

    if (action === "add" && addBtn) {
        box.innerHTML = `
            <div class="quantity-box">
                <button class="cart-btn cart-btn-remove" data-id="${addBtn.dataset.id}">🗑</button>
                <span class="qty">1</span>
                <button class="cart-btn cart-btn-increase" data-id="${addBtn.dataset.id}">➕</button>
            </div>
        `;
    }
    else if (qtyBox) {
        let qtyEl = qtyBox.querySelector(".qty");
        let qty = parseInt(qtyEl.textContent);

        if (action === "increase") qty++;
        else if (action === "decrease") qty--;
        else if (action === "remove") qty = 0;

        const pid = qtyBox.querySelector("button").dataset.id;

        if (qty <= 0) {
            box.innerHTML = `<button class="cart-btn cart-btn-add" data-id="${pid}">Add to Cart</button>`;
        } 
        else if (qty === 1) {
            qtyBox.innerHTML = `
                <button class="cart-btn cart-btn-remove" data-id="${pid}">🗑</button>
                <span class="qty">${qty}</span>
                <button class="cart-btn cart-btn-increase" data-id="${pid}">➕</button>
            `;
        } 
        else {
            qtyBox.innerHTML = `
                <button class="cart-btn cart-btn-decrease" data-id="${pid}">➖</button>
                <span class="qty">${qty}</span>
                <button class="cart-btn cart-btn-increase" data-id="${pid}">➕</button>
            `;
        }
    }
}
</script>
</body>
</html>
