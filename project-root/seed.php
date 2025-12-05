<?php
require __DIR__ . '/app/db.php';

$email = 'admin@local.test';

// check if admin already exists
$check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$check->execute([$email]);

if ($check->fetchColumn() == 0) {
    // not exists → insert
    $hash = password_hash('Admin@123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin', $email, $hash, 'admin']);
    echo "✅ Admin seeded successfully (new user created).\n";
} else {
    // already exists → no error, just success message
    echo "✅ Admin already exists (no new insert needed).\n";
}
