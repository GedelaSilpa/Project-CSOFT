<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch all careers
$stmt = $pdo->query("SELECT * FROM career WHERE is_active = 'Y' ORDER BY created_at DESC");
$careers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all career_ids this user has applied for
$appliedStmt = $pdo->prepare("SELECT career_id FROM career_applications WHERE user_id = ?");
$appliedStmt->execute([$user_id]);
$applied_ids = $appliedStmt->fetchAll(PDO::FETCH_COLUMN); // array of career_ids

// Fetch application counts for each career
$countStmt = $pdo->query("
    SELECT career_id, COUNT(*) AS total_applications
    FROM career_applications
    GROUP BY career_id
");
$appCounts = [];
foreach ($countStmt as $row) {
    $appCounts[$row['career_id']] = $row['total_applications'];
}

// Helper function to show time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "Just now";
    $minutes = round($diff / 60);
    if ($minutes < 60) return "$minutes minute" . ($minutes > 1 ? "s" : "") . " ago";
    $hours = round($diff / 3600);
    if ($hours < 24) return "$hours hour" . ($hours > 1 ? "s" : "") . " ago";
    $days = round($diff / 86400);
    if ($days < 7) return "$days day" . ($days > 1 ? "s" : "") . " ago";
    $weeks = round($diff / 604800);
    if ($weeks < 4) return "$weeks week" . ($weeks > 1 ? "s" : "") . " ago";
    $months = round($diff / 2600640);
    if ($months < 12) return "$months month" . ($months > 1 ? "s" : "") . " ago";
    $years = round($diff / 31207680);
    return "$years year" . ($years > 1 ? "s" : "") . " ago";
}
?>

<div class="career-view-container">
    <h1 style="text-align:center; margin-bottom:30px;">Career Opportunities</h1>

    <?php if ($careers): ?>
        <div class="career-items1">
            <?php foreach ($careers as $career): ?>
<div class="career-card1">
    <!-- Top Right Posted Time -->
    <div class="career-posted-top">
        🕒 <?= timeAgo($career['created_at']); ?>
    </div>

    <h3><?= htmlspecialchars($career['job_role']); ?> <?= $career['is_active'] == 'N' ? '(Inactive)' : '' ?></h3>
    <p><strong>Description:</strong> <?= htmlspecialchars($career['job_description']); ?></p>
    <p><strong>Skill Set:</strong> <?= htmlspecialchars($career['skill_set']); ?></p>
    <p><strong>Eligibility:</strong> <?= htmlspecialchars($career['eligibility']); ?></p>
    <p><strong>Vacancies:</strong> <?= htmlspecialchars($career['vacancies']); ?></p>

    <?php if ($career['is_active'] == 'Y'): ?>
        <?php if (in_array($career['id'], $applied_ids)): ?>
            <span class="applied-btn">✅ Applied</span>
        <?php else: ?>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user'): ?>
                <a href="career_apply.php?career_id=<?= $career['id']; ?>" class="apply-btn">Apply Now</a>
            <?php else: ?>
                <a href="login.php" class="apply-btn">Login to Apply</a>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Bottom Right Applicants Count -->
    <div class="career-applicants-bottom">
        👥 Applicants: <?= $appCounts[$career['id']] ?? 0; ?>
    </div>
</div>

            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;">No career opportunities available.</p>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="assets/css/style.css">
