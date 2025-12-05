<?php
session_start();
require __DIR__ . '/../../app/db.php';
require __DIR__ . '/../../app/auth.php';

// Require login and restrict only to admins
require_auth('admin');

// Fetch contacts
$stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
$contacts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Messages</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; }
    th { background-color: #f4f4f4; }
    .topbar { margin-bottom: 15px; }
</style>
</head>
<body>
    <div class="topbar">
        <h1>Contact Messages</h1>
        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> 
        | <a href="../logout.php">Logout</a></p>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Message</th>
            <th>Created At</th>
        </tr>
        <?php foreach($contacts as $c): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['message']) ?></td>
            <td><?= $c['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
