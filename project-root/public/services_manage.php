<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$error = '';
$success = '';
$editService = null;

// --- Handle Add/Edit Service ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    $title = trim($_POST['title']);
    $icon = trim($_POST['icon']);
    $description = trim($_POST['description']);
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

    if ($title === '' || $icon === '' || $description === '') {
        $error = "All fields are required!";
    } else {
        if ($service_id > 0) {
            // UPDATE query without updated_at (or add the column in DB)
            $stmt = $pdo->prepare("UPDATE services SET title=?, icon=?, description=? WHERE id=?");
            $stmt->execute([$title, $icon, $description, $service_id]);
            $success = "Service updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO services (title, icon, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$title, $icon, $description]);
            $success = "New service added successfully!";
        }
    }
}

// // --- Handle Delete ---
// if (isset($_GET['delete_service_id']) && $_SESSION['user_type'] === 'admin') {
//     $id = intval($_GET['delete_service_id']);
//     $stmt = $pdo->prepare("DELETE FROM services WHERE id=?");
//     $stmt->execute([$id]);
//     header("Location: services_manage.php#services_manage"); // reload page
//     exit;
// }
if (isset($_GET['delete_service_id'])) {
    $id = intval($_GET['delete_service_id']);

    try {
        // ✅ Only mark as inactive, not deleting or updating primary key
        $stmt = $pdo->prepare("UPDATE services SET is_active = 'N' WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: " . $_SERVER['PHP_SELF'] . "#services_manage");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to mark service inactive: " . $e->getMessage();
    }
}

// --- Fetch services ---
// $services = $pdo->query("SELECT * FROM services ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$services = $pdo->query("SELECT * FROM services WHERE is_active = 'Y' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch service to edit ---
if (isset($_GET['edit_id']) && $_SESSION['user_type'] === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id=? LIMIT 1");
    $stmt->execute([intval($_GET['edit_id'])]);
    $editService = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<section id="services_manage" class="services">
    <h1 style="text-align:center; color: navy; margin-bottom:20px;">Manage Services</h1>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <?php if($success) echo "<p class='success'>$success</p>"; ?>

    <div class="services-admin-container">
        <!-- LEFT FORM -->
        <div class="services-form">
            <form method="post">
                <input type="hidden" name="service_id" value="<?= $editService['id'] ?? 0 ?>">
                <label>Service Title:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($editService['title'] ?? '') ?>" required>
                
                <label>Font Awesome Icon class:</label>
                <input type="text" name="icon" value="<?= htmlspecialchars($editService['icon'] ?? '') ?>" required>
                <small>Example: fa-solid fa-stethoscope</small>
                
                <label>Service Description:</label>
                <textarea name="description" rows="4" required><?= htmlspecialchars($editService['description'] ?? '') ?></textarea>
                
                <button type="submit"><?= $editService ? 'Update Service' : 'Add Service' ?></button>
                <?php if($editService): ?>
                    <a href="index.php#services_manage" style="margin-left:10px; color:navy;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- RIGHT EXISTING SERVICES -->
        <div class="services-list">
            <div class="services-list-header">
                <h3>Existing Services</h3>
                <button id="newServiceBtn">New +</button>
            </div>
            <div class="services-items">
                <?php if(count($services) > 0): ?>
                <?php foreach($services as $service): ?>
                        <div class="service-card1">
                            <div class="service-header">
                                <i class="<?= htmlspecialchars($service['icon']) ?>"></i>
                                <strong><?= htmlspecialchars($service['title']) ?></strong>
                                <div class="service-actions">
                                    <!-- EDIT -->
                                     <a href="?edit_id=<?= $service['id']; ?>#services_manage" title="Edit" class="edit-btn">
                                <i class="fa-solid fa-pencil"></i>
                            </a>
                                    <!-- DELETE -->
                                    <!-- <a href="?delete_service_id=<?= $service['id']; ?>#service" title="Delete" class="delete-btn" onclick="return confirm('Delete this service?');"> -->
                                        <a href="?delete_service_id=<?= $service['id']; ?>#services_manage" title="Delete" class="delete-btn" onclick="return confirm('Delete this service?');">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                                </div>
                            </div>
                            <p><?= htmlspecialchars($service['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No services found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<link rel="stylesheet" href="assets/css/style.css">
<script>
// Reset form on New +
document.getElementById('newServiceBtn').addEventListener('click', function() {
    window.location.href = 'index.php#services_manage';
});

</script>
