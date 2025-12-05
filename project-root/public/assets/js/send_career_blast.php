#!/usr/bin/env php
<?php

// Make sure script runs only from CLI (or via controlled invocation)
if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI.\n");
}

// Include the Composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// --- Configuration / constants ---
define('LOCK_FILE', __DIR__ . '/send_career_blast.lock');
define('LOG_FILE', __DIR__ . '/send_career_blast.log');

// Your domain / base URL
define('BASE_URL', 'https://yourdomain.com');

// PHPMailer credentials
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USERNAME', 'shilpagedela@gmail.com');
define('MAIL_PASSWORD', 'psgfwvunpebkbtuo');  // Use your app password
define('MAIL_FROM_EMAIL', 'shilpagedela@gmail.com');
define('MAIL_FROM_NAME', 'CSOFT Healthcare Solutions');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS);

// Time to send emails (in HH:MM format, e.g., 09:00 for 9 AM)
define('SEND_TIME', '16:45');

// ------------------ helper functions ------------------

// Write a message to log (with timestamp)
function logMsg($msg) {
    $ts = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$ts] $msg\n", FILE_APPEND);
}

// Acquire lock (returns true if lock acquired, false if already locked)
function acquireLock() {
    if (file_exists(LOCK_FILE)) {
        // Optionally check if lock is stale (e.g., older than some threshold)
        return false;
    }
    $pid = getmypid();
    file_put_contents(LOCK_FILE, $pid);
    return true;
}

// Release lock
function releaseLock() {
    if (file_exists(LOCK_FILE)) {
        unlink(LOCK_FILE);
    }
}

// Gracefully exit, ensuring lock is removed
function safeExit($code = 0) {
    releaseLock();
    exit($code);
}

// ------------------ Main script logic ------------------

// Acquire lock
if (!acquireLock()) {
    logMsg("Another instance is already running. Exiting.");
    safeExit(0);
}

// Check if it's the right time to run the script (only at 16:15)
$currentTime = date('H:i');
if ($currentTime !== SEND_TIME) {
    logMsg("It is not the scheduled time to run the script. Current time: $currentTime. Exiting.");
    safeExit(0);  // Exit if it's not the scheduled time
}

// Autoload / bootstrap / DB
require_once __DIR__ . '/../app/db.php';  // Adjust the path to your DB connection
require_once __DIR__ . '/../vendor/autoload.php';  // Composer autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // 1. Fetch active careers
    $stmt = $pdo->query("SELECT * FROM career WHERE is_active = 'Y'");
    $careers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$careers) {
        logMsg("No active careers found. Nothing to send.");
        safeExit(0);
    }

    // 2. Pick one random career
    $randomIndex = array_rand($careers);
    $career = $careers[$randomIndex];

    // 3. Fetch users
    $stmt2 = $pdo->query("SELECT id, name, email FROM users WHERE email IS NOT NULL");
    $users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    if (!$users) {
        logMsg("No users with email found. Exiting.");
        safeExit(0);
    }

    // 4. Loop and send
    foreach ($users as $user) {
        $toEmail = $user['email'];
        $toName = $user['name'];

        $jobRole = htmlspecialchars($career['job_role'], ENT_QUOTES, 'UTF-8');
        $jobDesc = htmlspecialchars($career['job_description'], ENT_QUOTES, 'UTF-8');
        $applyLink = BASE_URL . '/apply.php?career_id=' . intval($career['id']);

        // Build HTML email body
        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; color: #333; }
    .container { max-width:600px; margin:0 auto; padding:20px; border:1px solid #ddd; border-radius:8px; }
    .header { background-color: #0066cc; color: #fff; padding:20px; text-align:center; border-top-left-radius:8px; border-top-right-radius:8px; }
    .body { padding:20px; }
    .button { display:inline-block; background-color:#0066cc; color:#fff; padding:12px 20px; text-decoration:none; border-radius:4px; }
    .button:hover { background-color:#0055a3; }
    .footer { margin-top:30px; font-size:12px; color:#999; text-align:center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>Opportunity: {$jobRole}</h2>
    </div>
    <div class="body">
      <p>Hi {$toName},</p>
      <p>We wanted to share this career opportunity with you:</p>
      <p><strong>{$jobDesc}</strong></p>
      <p>
        <a href="{$applyLink}" class="button">Apply Now</a>
      </p>
      <p>If you're interested, click the button above to apply.</p>
    </div>
    <div class="footer">
      &copy; {date('Y')} CSOFT Healthcare Solutions
    </div>
  </div>
</body>
</html>
HTML;

        // Plain text fallback
        $plainBody = "Hello {$toName},\n\n"
                   . "Opportunity: {$jobRole}\n\n"
                   . "{$jobDesc}\n\n"
                   . "Apply here: {$applyLink}\n\n"
                   . "CSOFT Healthcare Solutions";

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port = MAIL_PORT;

            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "New Job Opportunity: {$jobRole}";
            $mail->Body = $htmlBody;
            $mail->AltBody = $plainBody;

            $mail->send();
            logMsg("Email sent to {$toEmail}");
        } catch (Exception $ex) {
            logMsg("Failed to send to {$toEmail} — Error: " . $mail->ErrorInfo);
        }
    }

} catch (Exception $e) {
    logMsg("Fatal error in script: " . $e->getMessage());
    safeExit(1);
}

// All done
safeExit(0);
