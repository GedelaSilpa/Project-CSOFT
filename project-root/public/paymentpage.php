<?php
session_start();
require_once __DIR__ . '/../app/auth.php';

if (!isset($_SESSION['pending_order_id'])) {
    header("Location: checkout.php");
    exit;
}

$order_id = $_SESSION['pending_order_id'];

$stmt = $pdo->prepare("SELECT total_amount FROM orders WHERE id=?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

$total = $order['total_amount'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Select Payment | CSOFT</title>

<style>

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #f7f7f8;
}

.container {
    max-width: 650px;
    margin: 30px auto;
    background: #fff;
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
}

h1 {
    text-align: center;
    font-weight: 600;
    color: #6a1b9a;
    margin-bottom: 25px;
}

/* Payment card styles */
.payment-card {
    display: flex;
    align-items: center;
    padding: 10px;
    border: 2px solid #eee;
    border-radius: 14px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: 0.3s;
    background: #faf9fc;
}

.payment-card img {
    width: 38px;
    margin-right: 15px;
}

.payment-card:hover {
    border-color: #6a1b9a;
    background: #f2e7f7;
}

.payment-card.selected {
    border-color: #6a1b9a;
    background: #f4e3ff;
}

.payment-info {
    display: none;
    background: #f5f4fa;
    padding: 18px;
    border-radius: 12px;
    margin-bottom: 18px;
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Button */
.btn-pay {
    width: 100%;
    background: linear-gradient(45deg, #6a1b9a, #8e24aa);
    color: white;
    padding: 14px;
    border: none;
    border-radius: 30px;
    font-size: 17px;
    cursor: pointer;
    font-weight: 600;
    margin-top: 15px;
}

.btn-pay:hover {
    background: linear-gradient(45deg, #4b0075, #6a1b9a);
}

.amount-box {
    text-align: center;
    margin-bottom: 20px;
    background: #efecf3;
    padding: 15px;
    border-radius: 14px;
    font-size: 18px;
    color: #333;
    font-weight: 600;
}
.payment-card img {
    width: 30px;
    height: 30px;
    object-fit: contain;
    margin-right: 15px;
    border-radius: 8px;
}


</style>
</head>

<body>

<div class="container">

    <h1>Choose Your Payment Method</h1>

    <div class="amount-box">
        Payable Amount: <span style="color:#6a1b9a;">₹<?= number_format($total,2) ?></span>
    </div>

    <input type="hidden" id="totalAmount" value="<?= $total ?>">
    <input type="hidden" id="order_id" value="<?= $order_id ?>">

<!-- COD -->
<div class="payment-card" data-mode="COD">
    <img src="https://cdn-icons-png.flaticon.com/512/2331/2331970.png" alt="COD">
    Cash on Delivery
</div>

<div class="payment-info" id="info-COD">
    <h4>Cash on Delivery</h4>
    <p>You will pay ₹<?= number_format($total,2) ?> when your order is delivered.</p>
</div>

<!-- GOOGLE PAY -->
<div class="payment-card" data-mode="GPay">
    <img src="https://th.bing.com/th/id/OIP.FK8u8eAmsZqReKVg0_caXgHaHa?w=178&h=180&c=7&r=0&o=7&pid=1.7&rm=3" alt="Google Pay">
    Google Pay
</div>

<div class="payment-info" id="info-GPay">
    <h4>Google Pay</h4>
    <p>UPI ID: <strong>csofthealth@oksbi</strong></p>
</div>

<!-- PHONE PE -->
<div class="payment-card" data-mode="PhonePe">
    <img src="https://th.bing.com/th/id/OIP.g0SFf47JY0xckIe5x7W29QAAAA?w=176&h=185&c=7&r=0&o=7&pid=1.7&rm=3" alt="PhonePe">
    PhonePe
</div>

<div class="payment-info" id="info-PhonePe">
    <h4>PhonePe</h4>
    <p>UPI ID: <strong>csofthealth@oksbi</strong></p>
</div>

<!-- UPI -->
<div class="payment-card" data-mode="UPI">
    <img src="https://th.bing.com/th/id/OIP.FjcQympKavILetHp_LBeNwHaCn?w=304&h=123&c=7&r=0&o=7&pid=1.7&rm=3" alt="UPI">
    UPI
</div>

<div class="payment-info" id="info-UPI">
    <h4>UPI Payment</h4>
    <p>UPI ID: <strong>csofthealth@oksbi</strong></p>
</div>

<!-- PAYPAL -->
<div class="payment-card" data-mode="PayPal">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/500px-PayPal.svg.png" alt="PayPal">
    PayPal
</div>

<!-- PAYPAL SECTION -->
<div class="payment-info" id="info-PayPal">
    <h4>Pay via PayPal</h4>
    <button id="startPaypal" 
        style="background:#ffc439;padding:10px 110px;border:none;border-radius:8px;cursor:pointer;font-size:15px;">
        Pay ₹<span id="payAmount"><?= number_format($total, 2) ?></span> Now
    </button>
</div>

    <button class="btn-pay" id="confirmPayBtn">Confirm & Continue</button>

</div>

<script>

const cards = document.querySelectorAll(".payment-card");
let selectedMode = null;

cards.forEach(card => {
    card.addEventListener("click", () => {

        cards.forEach(c => c.classList.remove("selected"));
        card.classList.add("selected");

        selectedMode = card.getAttribute("data-mode");

        document.querySelectorAll(".payment-info").forEach(div => {
            div.style.display = "none";
        });

        document.getElementById("info-" + selectedMode).style.display = "block";
    });
});

document.getElementById("confirmPayBtn").addEventListener("click", () => {

    if (!selectedMode) {
        alert("Please select a payment method.");
        return;
    }

    if (selectedMode === "PayPal") {
        alert("Please click the yellow PayPal button above to continue.");
        return;
    }

    window.location.href = "confirm_payment.php?mode=" + selectedMode;
});

// PayPal Logic
document.getElementById('startPaypal').addEventListener('click', () => {
    const orderId = <?= json_encode($order_id) ?>;

    fetch('create-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId })
    })
    .then(r => r.json())
    .then(res => {
        console.log(res);
        if (!res.paypal_response || !res.paypal_response.links) {
            alert("PayPal error. See console.");
            return;
        }
        const approveLink = res.paypal_response.links.find(l => l.rel === 'approve');
        if (approveLink) {
            window.location.href = approveLink.href;
        } else {
            alert("No approval link returned by PayPal.");
        }
    })
    .catch(e => console.error(e));
});


</script>

</body>
</html>
