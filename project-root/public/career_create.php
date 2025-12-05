<?php
ob_start(); // Start output buffering to allow header redirects
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php'; // Make sure you have PDO connection here
require_auth('admin');

$error = '';
$success = '';

if (isset($_GET['delete_career_id'])) {
    $id = intval($_GET['delete_career_id']);

    try {
        // ✅ Only mark as inactive, not deleting or updating primary key
        $stmt = $pdo->prepare("UPDATE career SET is_active = 'N' WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: " . $_SERVER['PHP_SELF'] . "#career");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to mark career inactive: " . $e->getMessage();
    }
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['career_id'] ?? 0);
    $job_role = trim($_POST['job_role'] ?? '');
    $job_description = trim($_POST['job_description'] ?? '');
    $skill_set = trim($_POST['skill_set'] ?? '');
    $eligibility = trim($_POST['eligibility'] ?? '');
    $vacancies = intval($_POST['vacancies'] ?? 0);
    $is_active = $_POST['is_active'] ?? 'Y';

    if (!$job_role || !$job_description || !$skill_set || !$eligibility) {
        $error = 'Please fill all required fields.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE career SET job_role=?, job_description=?, skill_set=?, eligibility=?, vacancies=?, is_active=? WHERE id=?");
            if ($stmt->execute([$job_role, $job_description, $skill_set, $eligibility, $vacancies, $is_active, $id])) {
                header("Location: ".$_SERVER['PHP_SELF']."#career");
                exit; // Prevent double submission
            } else {
                $error = 'Failed to update career. Try again.';
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO career (job_role, job_description, skill_set, eligibility, vacancies, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$job_role, $job_description, $skill_set, $eligibility, $vacancies, $is_active])) {
                header("Location: ".$_SERVER['PHP_SELF']."#career");
                exit; // Prevent double submission
            } else {
                $error = 'Failed to create career. Try again.';
            }
        }
    }
}

// Fetch all careers
// $careers = $pdo->query("SELECT * FROM career ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$careers = $pdo->query("SELECT * FROM career WHERE is_active = 'Y' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch career to edit
$editCareer = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM career WHERE id=? LIMIT 1");
    $stmt->execute([intval($_GET['edit_id'])]);
    $editCareer = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<h1 style="text-align:center; color: navy; margin-bottom:20px;">Careers</h1>
<!-- career Admin Container -->
<div class="career-admin-container">
    <!-- LEFT: Create/Edit Form -->
    <div class="career-form">
        <h2><?= $editCareer ? 'Edit career' : 'Create Career Opportunity' ?></h2>
        <?php if($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <?php if($success): ?><p class="success"><?= $success ?></p><?php endif; ?>

        <form method="POST" id="careerForm">
            <input type="hidden" name="career_id" value="<?= $editCareer['id'] ?? '' ?>">
            <input type="text" name="job_role" placeholder="Job Role" value="<?= $editCareer['job_role'] ?? '' ?>" required>
            <textarea name="job_description" placeholder="Job Description" rows="3" required><?= $editCareer['job_description'] ?? '' ?></textarea>
            <textarea name="skill_set" placeholder="Skill Set Required" rows="3" required><?= $editCareer['skill_set'] ?? '' ?></textarea>
            <input type="text" name="eligibility" placeholder="Eligibility Criteria" value="<?= $editCareer['eligibility'] ?? '' ?>" required>
            <input type="number" name="vacancies" placeholder="Vacancies" min="0" value="<?= $editCareer['vacancies'] ?? 0 ?>">
            <select name="is_active">
                <option value="Y" <?= (isset($editCareer['is_active']) && $editCareer['is_active']=='Y') ? 'selected' : '' ?>>Active</option>
                <option value="N" <?= (isset($editCareer['is_active']) && $editCareer['is_active']=='N') ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button type="submit"><?= $editCareer ? 'Update career' : 'Create career' ?></button>
        </form>
    </div>

    <!-- RIGHT: Existing Careers -->
    <div class="career-list">
        <div class="career-list-header">
            <h2>Existing Careers</h2>
            <button id="newCareerBtn">New +</button>
        </div>

        <div class="career-items">
            <?php foreach($careers as $career): ?>
                <div class="career-card">
                    <div class="career-header">
                        <h4><?= htmlspecialchars($career['job_role']); ?></h4>
                        <div class="icon-actions">
                            <a href="?edit_id=<?= $career['id']; ?>#career" title="Edit" class="edit-btn">
                                <i class="fa-solid fa-pencil"></i>
                            </a>
                            <a href="?delete_career_id=<?= $career['id']; ?>#career" title="Delete" class="delete-btn" onclick="return confirm('Delete this career?');">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="career-content">
                        <p><span class="label1">Description:</span> <?= htmlspecialchars($career['job_description']); ?></p>
                        <p><span class="label1">Skills:</span> <?= htmlspecialchars($career['skill_set']); ?></p>
                        <p><span class="label1">Eligibility:</span> <?= htmlspecialchars($career['eligibility']); ?></p>
                        <p><span class="label1">Vacancies:</span> <?= htmlspecialchars($career['vacancies']); ?></p>
                        <p><span class="label1">Status:</span> <?= $career['is_active']=='Y' ? 'Active' : 'Inactive'; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/css/style.css">
<script>
// Reset form when clicking New +
document.getElementById('newCareerBtn').addEventListener('click', function() {
    window.location.href = '?#career';
});
</script>
