<?php
require_once __DIR__ . '/../app/auth.php';
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
$error = '';
 
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captchaVerified = $_POST['captcha_verified'] ?? '';
 
    if ($captchaVerified !== 'true') {
        $error = "Please complete the CAPTCHA correctly.";
    } else {
        if (login($email, password: $password)) {
            session_regenerate_id(true);
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@700&display=swap" rel="stylesheet">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - CSOFT HEALTHCARE SOLUTIONS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="main-wrapper1">
    <div class="login-box">
        <!-- <h2 class="login-title">CSOFT HEALTHCARE SOLUTIONS</h2> -->
      <h2 class="login-title">
    <span class="cs-soft">CSOFT</span><br>
    <span class="healthcare-solutions">HEALTHCARE SOLUTIONS</span>
</h2>

        <?php if($error): ?>
            <div class="error-popup"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
 
        <form method="POST" id="loginForm">
           
            <!-- Login Section -->
            <div id="loginSection" class="form-section active">
                <h3><i class="fa-solid fa-user"></i> User Login</h3>
                <input type="email1" name="email" placeholder="Email ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <div class="checkbox-area">
                    <input type="checkbox" id="humanCheck">
                    <label id="humanCheckLabel" for="humanCheck">Are you human?</label>
                </div>
            </div>
 
    <!-- Captcha Section -->
    <div id="captchaSection" class="form-section hidden">
        <div class="direction-guide">Rotate the dog to match the hand’s direction 🐶👆</div>
        <img id="hand3D" src="assets/captcha/hand_0.jpg" alt="Hand">
        <img id="animalImage" src="assets/captcha/fox_0.jpg" alt="Fox">
        <div class="captcha-controls">
            <button type="button" id="rotateLeft">⟲</button>
            <button type="button" id="rotateRight">⟳</button>
        </div>
        <button type="button" class="verify-btn" id="verifyCaptcha">✔️ Verify Captcha</button>
        <input type="hidden" name="captcha_verified" id="captcha_verified" value="false">
        <p id="captchaMsg"></p>
    </div>

    <div class="bottom-section">
        <button type="submit" id="loginBtn" disabled>Login</button>
        <div class="links">
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
            <p><a href="forgot-password.php">Forgot Password?</a></p>
        </div>
    </div>

        </form>
    </div>
</div>
 
<script>
const directions = [0,45,90,135,180,225,270,315];
const animalImage = document.getElementById('animalImage');
const hand3D = document.getElementById('hand3D');
const captchaMsg = document.getElementById('captchaMsg');
const hiddenInput = document.getElementById('captcha_verified');
const verifyBtn = document.getElementById('verifyCaptcha');
const leftBtn = document.getElementById('rotateLeft');
const rightBtn = document.getElementById('rotateRight');
const humanCheck = document.getElementById('humanCheck');
const humanLabel = document.getElementById('humanCheckLabel');
const loginBtn = document.getElementById('loginBtn');
const loginSection = document.getElementById('loginSection');
const captchaSection = document.getElementById('captchaSection');
 
let currentIndex = 0;
let targetIndex = Math.floor(Math.random() * directions.length);
let failCount = 0;
let locked = false;
 
hand3D.src = `assets/captcha/hand_${directions[targetIndex]}.jpg`;
 
function updateAnimal() {
    animalImage.src = `assets/captcha/fox_${directions[currentIndex]}.jpg`;
}
 
leftBtn.addEventListener('click', () => {
    if (locked) return;
    currentIndex = (currentIndex - 1 + directions.length) % directions.length;
    updateAnimal();
});
rightBtn.addEventListener('click', () => {
    if (locked) return;
    currentIndex = (currentIndex + 1) % directions.length;
    updateAnimal();
});
 
// Step 1: Checkbox click → Show CAPTCHA
humanCheck.addEventListener('change', () => {
    if (humanCheck.checked) {
        loginSection.classList.replace('active', 'hidden');
        setTimeout(() => {
            captchaSection.classList.replace('hidden', 'active');
        }, 400);
    }
});
 
// Step 2: CAPTCHA verification
verifyBtn.addEventListener('click', () => {
    if (locked) {
        captchaMsg.style.color = 'orange';
        captchaMsg.textContent = "⏳ Please wait before trying again.";
        return;
    }
 
    if (currentIndex === targetIndex) {
        captchaMsg.style.color = 'limegreen';
        captchaMsg.textContent = '✅ Verified Successfully!';
        hiddenInput.value = 'true';
        failCount = 0;

        loginBtn.disabled = false;
        loginBtn.style.opacity = '1';

        setTimeout(() => {
            captchaSection.classList.replace('active', 'hidden');
            loginSection.classList.replace('hidden', 'active');
            humanLabel.textContent = " Human Verified ";
            humanLabel.style.color = "limegreen";
        }, 900);
 
    } else {
        failCount++;
        hiddenInput.value = 'false';
        captchaMsg.style.color = 'red';
        captchaMsg.textContent = `❌ Incorrect! Attempt ${failCount}/3`;
 
        if (failCount >= 3) {
            locked = true;
            captchaMsg.style.color = 'orange';
            captchaMsg.textContent = "⏳ Too many failed attempts. Try again in 1 hour.";
            verifyBtn.disabled = true;
            leftBtn.disabled = true;
            rightBtn.disabled = true;
            setTimeout(() => {
                locked = false;
                failCount = 0;
                verifyBtn.disabled = false;
                leftBtn.disabled = false;
                rightBtn.disabled = false;
                captchaMsg.textContent = "🔄 Try again now.";
            }, 3600000);
        }
    }
});
</script>
</body>
</html>
 
 