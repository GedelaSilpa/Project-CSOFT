<?php
$reason = $_GET['reason'] ?? 'unknown';
?>
<!DOCTYPE html>
<html>
<head>
<title>Payment Failed</title>
<meta charset="UTF-8">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #ffffff;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .box {
        max-width: 450px;
        width: 90%;
        background: #fff;
        border: 1px solid #e4e4e4;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }
    h1 {
        color: #D32F2F;
        font-size: 24px;
        margin-bottom: 10px;
    }
    p {
        color: #444;
        font-size: 16px;
        margin-bottom: 20px;
    }
    a {
        display: inline-block;
        background: #ff9900;
        padding: 12px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        color: #000;
    }
</style>
</head>

<body>

<div class="box">
    <h1>❌ Payment Failed</h1>

    <p>
        <?php
            switch ($reason) {
                case 'payment_cancelled':
                    echo "Your payment was cancelled.";
                    break;
                case 'paypal_failed':
                    echo "PayPal was unable to process your payment.";
                    break;
                case 'session_expired':
                    echo "Your session expired. Please try again.";
                    break;
                case 'missing_token':
                    echo "Invalid request. Missing PayPal token.";
                    break;
                default:
                    echo "Something went wrong.";
            }
        ?>
    </p>

    <a href="cart.php">Return to Cart</a>
</div>

</body>
</html>
