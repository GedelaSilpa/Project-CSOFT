<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
$error = '';
$success = '';

// Fetch SMTP credentials from the AdminDetails table
$stmt = $pdo->prepare("SELECT SMTPUsername, SMTPPassword FROM AdminDetails WHERE IsActive = 1 LIMIT 1");
$stmt->execute();
$smtpDetails = $stmt->fetch();

if (!$smtpDetails) {
    die('SMTP credentials not found in the database.');
}

$smtpUsername = $smtpDetails['SMTPUsername'];
$smtpPassword = $smtpDetails['SMTPPassword'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
 
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
 
        if ($user) {
            // Parse first name
            $nameParts = explode(' ', $user['name']);
            $firstName = ucfirst($nameParts[0]);
 
            // Generate new password
            date_default_timezone_set('Asia/Kolkata'); // Set your timezone
            $newPassword = $firstName . date('Hi') . '@' . rand(1000, 9999);
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
 
            // Update password in DB
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->execute([$passwordHash, $email]);
 
            // Send email with new password
            $mail = new PHPMailer(true);
            try {
                // SMTP settings (same as your signup mail config)
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUsername;  // your SMTP username
                $mail->Password = $smtpPassword;       // your SMTP password or app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
 
                // Sender & recipient
                $mail->setFrom($smtpUsername, 'CSOFT Healthcare Solutions');
                $mail->addAddress($email, $user['name']);
 
                // Email content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset - CSOFT Healthcare Solutions';
 
                $bodyHtml = '<p>Hello ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ',</p>
                    <p>Your password has been reset. Here are your new login credentials:</p>
                    <ul>
                      <li><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</li>
                      <li><strong>Password:</strong> ' . htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8') . '</li>
                    </ul>
                    <p>Please login and consider changing your password after logging in.</p>
                    <p><a href="https://chandusoft.com/login.php">Login here</a></p>
                    <p>If you did not request this, please contact support immediately.</p>';
 
                $bodyPlain = "Hello {$user['name']},\n\n"
                    . "Your password has been reset. Here are your new login credentials:\n"
                    . "Email: $email\n"
                    . "Password: $newPassword\n\n"
                    . "Please login and consider changing your password after logging in.\n"
                    . "Login here: https://chandusoft.com/login.php\n\n"
                    . "If you did not request this, please contact support immediately.";
 
                $mail->Body = $bodyHtml;
                $mail->AltBody = $bodyPlain;
 
                $mail->send();
 
                $success = "A new password has been sent to your email address.";
            } catch (Exception $e) {
                $error = "Failed to send email. Please try again later.";
                error_log("Mailer error (forgot-password): " . $mail->ErrorInfo);
            }
        } else {
            $error = 'Email not found.';
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Forgot Password - CSOFT Healthcare Solutions</title>

<!-- Import Inter Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">

<!-- <style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: var(--font); }
html, body { height: 100%; scroll-behavior: smooth; }
</style> -->

</head>

<body class="forgot-page">
<div class="forgot-container">
    <h2>Forgot Password</h2>
    <?php if (!empty($error)) echo "<div class='error1'>$error</div>"; ?>
    <?php if (!empty($success)) echo "<div class='success1'>$success</div>"; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter your registered email" required />
        <button type="submit">Reset Password</button>
    </form>

    <div class="back-login">
        <p>Remembered your password? <a href="login.php">Back to Login</a></p>
    </div>
</div>
</body>
</html>

 