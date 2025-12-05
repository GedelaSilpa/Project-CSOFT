<?php
ob_start();
require_once __DIR__ . '/../app/auth.php';
require_auth('admin');
require_once __DIR__ . '/../app/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
<header>Admin Dashboard</header>
<div class="container">
<?php
try {
    //Fetch total counts
    $careerCount = $pdo->query("SELECT COUNT(*) FROM JobApplications")->fetchColumn();
    $contactCount = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    $serviceCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Fetch latest records
    $latestCareers = $pdo->query("SELECT * FROM JobApplications ORDER BY career_id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $latestContacts = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $latestServices = $pdo->query("SELECT * FROM services ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $latestUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<p style='color:red;'>Error loading dashboard data: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!-- Summary Cards -->
<div class="dashboard-summary">
    <div class="card">
        <h3>Career Applications</h3>
        <p><?= $careerCount ?></p>
    </div>
    <div class="card">
        <h3>Contacts</h3>
        <p><?= $contactCount ?></p>
    </div>
    <div class="card">
        <h3>Services</h3>
        <p><?= $serviceCount ?></p>
    </div>
    <div class="card">
        <h3>Users</h3>
        <p><?= $userCount ?></p>
    </div>
</div>

<!--Sections in 2x2 Grid -->
<div class="recent-grid">
<!-- career Applications -->
<section>
    <h3>Career Applications</h3>
    <div class="section-content">
        <?php if (!empty($latestCareers)): ?>
            <table>
                <tr><th>Name</th><th>Job Role</th><th>Email Id</th><th>Phone Number</th><th>Education</th><th>Experience</th>
                <th>Expected_salary</th><th>Resume</th><th>Applied_at</th></tr>
                <?php foreach ($latestCareers as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['full_name']) ?></td>
                        <td><?= htmlspecialchars($c['job_role']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><?= htmlspecialchars($c['education']) ?></td>
                        <td><?= htmlspecialchars($c['experience']) ?></td>
                        <td><?= htmlspecialchars($c['expected_salary']) ?></td>
                        <td><?= htmlspecialchars($c['resume_path']) ?></td>
                        <td><?= htmlspecialchars($c['applied_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="no-data">No applications found.</p>
        <?php endif; ?>
    </div>
</section>
  
<!-- Contacts -->
<section>
    <h3>Contacts</h3>
    <div class="section-content">
        <?php if (!empty($latestContacts)): ?>
            <table>
                <tr><th>Full Name</th><th>Email Id</th><th>Message</th></tr>
                <?php foreach ($latestContacts as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars(substr($c['message'], 0, 60)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="no-data">No contact messages found.</p>
        <?php endif; ?>
    </div>
</section>

    <!-- Services -->
    <section>
        <h3>Services</h3>
        <div class="section-content">
            <?php if (!empty($latestServices)): ?>
                <table>
                    <tr><th>Title</th><th>Description</th></tr>
                    <?php foreach ($latestServices as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['title']) ?></td>
                            <td><?= htmlspecialchars(substr($s['description'], 0, 60)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="no-data">No services found.</p>
            <?php endif; ?>
        </div>
    </section>
    
<!-- Users -->
<section>
    <h3>Users</h3>
    <div class="section-content">
        <?php if (!empty($latestUsers)): ?>
            <table>
                <tr><th>Name</th><th>Email</th><th>Created</th></tr>
                <?php foreach ($latestUsers as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="no-data">No users found.</p>
        <?php endif; ?>
    </div>
</section>

</div>
</div>
</body>
</html>
