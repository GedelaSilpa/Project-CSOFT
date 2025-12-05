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
    $firstName = ucfirst(strtolower(trim($_POST['first_name'] ?? '')));
    $lastName = ucfirst(strtolower(trim($_POST['last_name'] ?? '')));
    $email = trim($_POST['email'] ?? '');
    $fullName = $firstName . ' ' . $lastName;

    if (!$firstName || !$lastName || !$email) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Check for duplicate
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            date_default_timezone_set('Asia/Kolkata');
 
            // Generate password: FirstNameHHMM@Random4Digits
            $time = date('Hi'); // current time in HHMM
            $randomDigits = rand(1000, 9999);
            $generatedPassword = $firstName . $time . '@' . $randomDigits;

            // Hash password before storing
            $password_hash = password_hash($generatedPassword, PASSWORD_DEFAULT);

            // Insert into DB
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$fullName, $email, $password_hash])) {
                $mail = new PHPMailer(true);
                try {
                    // SMTP settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpUsername; // Fetch from DB
                    $mail->Password = $smtpPassword; // Fetch from DB
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Sender & recipient
                    $mail->setFrom($smtpUsername, 'CSOFT Healthcare Solutions');
                    $mail->addAddress($email, $fullName);

                    // Email content
                    $mail->isHTML(true);
                    $mail->Subject = 'Registration Successful - Your Login Credentials';

                    $bodyHtml = '<!DOCTYPE html>
<html lang="en">
  <head><meta charset="UTF-8"></head>
  <body>
    <div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;line-height:1.5;color:#333;">
      <h1 style="color:#0d6efd;">Welcome, ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '!</h1>
      <p>Thank you for registering at <strong>CSOFT Healthcare Solutions</strong>.</p>
      <p><strong>Your login credentials are:</strong></p>
      <ul>
        <li>Email: ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</li>
        <li>Password: <strong>' . htmlspecialchars($generatedPassword, ENT_QUOTES, 'UTF-8') . '</strong></li>
      </ul>
      <p>To access your account, click the button below:</p>
      <p>
        <a href="http://localhost/Project-CSOFT/project-root/public/login.php"
           style="background-color:#0d6efd;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">
          Login to your account
        </a>
      </p>
      <hr style="margin:30px 0; border:none; border-top:1px solid #eee;">
      <p>If you did not register, you can ignore this email.</p>
      <footer style="margin-top:30px;font-size:12px;color:#888;">
        CSOFT Healthcare Solutions • <a href="http://localhost/Project-CSOFT/project-root/public/index.php">chandusoft.com</a><br>
        &copy; ' . date('Y') . ' CSOFT Healthcare Solutions. All rights reserved.
      </footer>
    </div>
  </body>
</html>';

                    $bodyPlain = "Welcome, $fullName!\n\n"
                        . "Thank you for registering at CSOFT Healthcare Solutions.\n\n"
                        . "Your login credentials:\n"
                        . "Email: $email\n"
                        . "Password: $generatedPassword\n\n"
                        . "Login here: http://localhost/Project-CSOFT/project-root/public/login.php\n\n"
                        . "If you didn’t register, you can ignore this email.\n";

                    $mail->Body = $bodyHtml;
                    $mail->AltBody = $bodyPlain;

                    $mail->send();

                    $success = 'Registration successful! Your login credentials have been sent to your email. <a href="login.php">Login here</a>.';
                } catch (Exception $e) {
                    $success = 'Registration successful! But we could not send confirmation email. <a href="login.php">Login here</a>.';
                    error_log("Mailer error (registration email): " . $mail->ErrorInfo);
                }
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Signup - CSOFT HEALTHCARE SOLUTIONS</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body class="signup-page">

<div class="main-wrapper">
  <section class="hero" style="min-height:100vh; justify-content:center; align-items:center;">
    <div class="signup-container">
      <h2>Signup</h2>

      <?php if ($error) echo "<p class='error'>$error</p>"; ?>
      <?php if ($success) echo "<p class='success'>$success</p>"; ?>

      <form method="POST">
        <input type="text" name="first_name" placeholder="First Name" required />
        <input type="text" name="last_name" placeholder="Last Name" required />
        <input type="email" name="email" placeholder="Email" required />
        <button type="submit" class="btn">Sign Up</button>
      </form>

      <div class="login-link">
        <p>
          <span>Already have an account?</span>
          <a href="login.php">Login here</a>
        </p>
      </div>
    </div>
  </section>
</div>

</body>
</html>
