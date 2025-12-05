<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../app/auth.php';
require_auth('user');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$career_id = isset($_GET['career_id']) ? intval($_GET['career_id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM career WHERE id = ? LIMIT 1");
$stmt->execute([$career_id]);
$career = $stmt->fetch();

if (!$career) die("Invalid job selected.");

$success = $error = '';

// Fetch SMTP credentials
$stmt = $pdo->prepare("SELECT SMTPUsername, SMTPPassword FROM AdminDetails WHERE IsActive = 1 LIMIT 1");
$stmt->execute();
$smtp = $stmt->fetch();
if (!$smtp) die('SMTP credentials not found.');

$smtpUsername = $smtp['SMTPUsername'];
$smtpPassword = $smtp['SMTPPassword'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Combine full name parts
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);

    // Combine address parts
    $house = trim($_POST['house']);
    $street = trim($_POST['street']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $country = trim($_POST['country']);
    $address = "$house, $street, $city, $state, $country";

    // Combine education info (human-readable)
    $education =
        "10th: Board=" . ($_POST['tenth_board'] ?? '') .
        ", School=" . ($_POST['tenth_school'] ?? '') .
        ", Year=" . ($_POST['tenth_year'] ?? '') .
        ", Marks=" . ($_POST['tenth_marks'] ?? '') . "; " .

        "12th: Board=" . ($_POST['twelfth_board'] ?? '') .
        ", School=" . ($_POST['twelfth_school'] ?? '') .
        ", Year=" . ($_POST['twelfth_year'] ?? '') .
        ", Marks=" . ($_POST['twelfth_marks'] ?? '') . "; " .

        "UG/PG: University=" . ($_POST['ugpg_board'] ?? '') .
        ", College=" . ($_POST['ugpg_college'] ?? '') .
        ", Year=" . ($_POST['ugpg_year'] ?? '') .
        ", Marks=" . ($_POST['ugpg_marks'] ?? '');

    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $experience = trim($_POST['experience']);
    $expected_salary = trim($_POST['expected_salary']);
    $cover_letter = trim($_POST['cover_letter']);

    // === File Upload: Profile Image ===
    $profile_image = '';
    if (!empty($_FILES['profile_image']['name'])) {
        $allowed_img = ['jpg', 'jpeg', 'png'];
        $max_size = 1 * 1024 * 1024; // 1MB
        $img_name = $_FILES['profile_image']['name'];
        $img_tmp = $_FILES['profile_image']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $img_size = $_FILES['profile_image']['size'];

        if (!in_array($img_ext, $allowed_img)) {
            $error = "Invalid image type. Only JPG, JPEG, PNG allowed.";
        } elseif ($img_size > $max_size) {
            $error = "Image too large. Maximum 1MB allowed.";
        } else {
            $img_dir = __DIR__ . "/uploads/images/";
            if (!is_dir($img_dir)) mkdir($img_dir, 0777, true);
            $safe_img = time() . "_" . preg_replace("/[^A-Za-z0-9_.-]/", "_", $img_name);
            $img_target = $img_dir . $safe_img;
            if (move_uploaded_file($img_tmp, $img_target)) {
                $profile_image = "uploads/images/" . $safe_img;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    // === File Upload: Resume ===
    $resume_path = '';
    if (empty($error) && !empty($_FILES['resume']['name'])) {
        $allowed_ext = ['pdf','doc','docx','jpg','jpeg','png'];
        $max_size = 2 * 1024 * 1024;
        $file_name = $_FILES['resume']['name'];
        $file_tmp = $_FILES['resume']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['resume']['size'];

        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Invalid resume type. Allowed: PDF, DOC, DOCX, JPG, PNG.";
        } elseif ($file_size > $max_size) {
            $error = "Resume too large. Maximum 2MB allowed.";
        } else {
            $target_dir = __DIR__ . "/uploads/resumes/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $safe_name = time() . "_" . preg_replace("/[^A-Za-z0-9_.-]/", "_", $file_name);
            $target_file = $target_dir . $safe_name;
            if (move_uploaded_file($file_tmp, $target_file)) {
                $resume_path = "uploads/resumes/" . $safe_name;
            } else {
                $error = "Failed to upload resume.";
            }
        }
    }

    // === Insert into DB ===
    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO career_applications 
            (user_id, career_id, full_name, email, phone, address, education, experience, expected_salary, profile_image, resume_path, cover_letter)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'], $career_id, $full_name, $email, $phone,
            $address, $education, $experience, $expected_salary, $profile_image, $resume_path, $cover_letter
        ]);

        try {
            // Send email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom($smtpUsername, 'CSOFT Healthcare Solutions');
            $mail->addAddress($email, $full_name);
            $mail->isHTML(true);
            $mail->Subject = "Application Received — " . htmlspecialchars($career['job_role']);
            $mail->Body = "Hello <strong>$full_name</strong>,<br>Thank you for applying for the position of <strong>{$career['job_role']}</strong>. We have received your application.";
            $mail->send();
        } catch (Exception $e) {
            // even if mail fails, continue success
        }

       // Redirect to show success message first
        header("Location: career_apply.php?career_id=$career_id&success=1");
        exit;
    }
}

// Success message after redirect (executed on GET)
if (isset($_GET['success'])) {
    $success = "✅ Application submitted successfully! Redirecting...";
    // Auto redirect after 3 seconds
    echo '<meta http-equiv="refresh" content="3;url=http://localhost/Project-CSOFT/project-root/public/index.php#career">';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Apply for <?= htmlspecialchars($career['job_role']); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
<!-- <style>
body {
    background-color: #ffffff;
    font-family: "Segoe UI", Arial, sans-serif;
    color: #001f54;
    margin: 0; padding: 0;
}

</style> -->
</head>
<body class="career-apply-page">

<div class="career-apply container">
    <h2>Apply for <?= htmlspecialchars($career['job_role']); ?></h2>

    <?php if ($success): ?><div class="success"><?= $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= $error; ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <!-- your existing form fields -->
        <!-- (no changes needed below this) -->
        <fieldset>
            <legend>Personal Details</legend>
            <div class="form-grid">
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                    <label>Middle Name</label>
                    <input type="text" name="middle_name">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                    <label>Email ID</label>
                    <input type="email" name="email" required>
                    <label>Phone Number</label>
                    <input type="text" name="phone" pattern="[0-9]{10}" placeholder="10-digit number" required>
                </div>

                <div class="resume-section">
                    <label>Upload Image (jpg/png-max 1MB)</label>
                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png" required>
                    <label style="margin-top:20px;">Upload Resume (pdf/doc/jpg/png-max 2MB)</label>
                    <input type="file" name="resume" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Address</legend>
            <div class="form-grid">
                <div>
                    <label>House/Door No.</label>
                    <input type="text" name="house">
                    <label>Street</label>
                    <input type="text" name="street">
                </div>
                <div>
                    <label>City</label>
                    <input type="text" name="city">
                    <label>State</label>
                    <input type="text" name="state">
                    <label>Country</label>
                    <input type="text" name="country">
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Education Details</legend>
            <h4>10th Class</h4>
            <div class="form-grid">
                <input type="text" name="tenth_board" placeholder="Board Name">
                <input type="text" name="tenth_school" placeholder="School Name">
                <input type="text" name="tenth_year" placeholder="Year of Passing">
                <input type="text" name="tenth_marks" placeholder="Marks/Percentage">
            </div>
            <h4>12th / Intermediate</h4>
            <div class="form-grid">
                <input type="text" name="twelfth_board" placeholder="Board Name">
                <input type="text" name="twelfth_school" placeholder="School/College Name">
                <input type="text" name="twelfth_year" placeholder="Year of Passing">
                <input type="text" name="twelfth_marks" placeholder="Marks/Percentage">
            </div>
            <h4>Undergraduate / Postgraduate</h4>
            <div class="form-grid">
                <input type="text" name="ugpg_board" placeholder="University Name">
                <input type="text" name="ugpg_college" placeholder="College Name">
                <input type="text" name="ugpg_year" placeholder="Year of Passing">
                <input type="text" name="ugpg_marks" placeholder="Marks/CGPA">
            </div>
        </fieldset>

        <fieldset>
            <legend>Experience & Salary</legend>
            <label>Experience</label>
            <input type="text" name="experience" placeholder="e.g., 2 years in IT, Fresher, etc.">
            <label>Expected Salary</label>
            <select name="expected_salary" required>
                <option value="">-- Select --</option>
                <option>1-2 LPA</option>
                <option>3-5 LPA</option>
                <option>6-8 LPA</option>
                <option>9-12 LPA</option>
                <option>12+ LPA</option>
            </select>
        </fieldset>

        <fieldset>
            <legend>Cover Letter (Optional)</legend>
            <textarea name="cover_letter" rows="4" placeholder="Write a brief cover letter (optional)"></textarea>
        </fieldset>

        <button type="submit">Submit Application</button>
    </form>
</div>

</body>
</html>
