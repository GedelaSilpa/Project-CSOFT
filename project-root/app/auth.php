<?php
// Debugging: Check if path is correct

// Include Composer's autoloader dynamically
require_once __DIR__ . '/../vendor/autoload.php';  // Correct path

// Start session only if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load .env file using Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');  // Ensure the path points to the root of your project
$dotenv->load();

require_once __DIR__ . '/db.php'; // Make sure this connects $pdo

/**
 * Login function for both admin and user
 */
function login($email, $password) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = $user['role']; // 'admin' or 'user'
    
        return true;
    }
    return false;
}

/**
 * Check if logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_type']);
}

/**
 * Require authentication (optional role restriction)
 */
function require_auth($role = null) {
    if (!is_logged_in()) {
        header('Location: /Project-CSOFT/project-root/public/login.php');
        exit;
    }

    if ($role && $_SESSION['user_type'] !== $role) {
        header("HTTP/1.1 403 Forbidden");
        echo "<h1>Access Denied</h1><p>You do not have permission to access this page.</p>";
        exit;
    }
}

/**
 * Logout
 */
function logout() {
    session_unset();
    session_destroy();
}
