<?php
ob_start();
session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

// 🛒 Cart Count (for logged-in users)
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS total FROM cart_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn() ?: 0;
}

$error = '';
$success = '';

// --- Fetch careers ---
$careers = $pdo->query("SELECT * FROM career ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$editCareer = null;
if (isset($_GET['edit_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM career WHERE id=? LIMIT 1");
    $stmt->execute([intval($_GET['edit_id'])]);
    $editCareer = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSOFT HEALTHCARE SOLUTIONS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"/>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navigation -->
<nav>
    <a href="index.php" class="logo-wrapper">
        <img src="assets/Captcha/Csoft_logo.png" alt="Company Logo" class="logo-img">
    </a>
    <ul>
        <li>
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
            <li><a href="dashboard.php">Dashboard</a></li>
        <?php else: ?>
            <li><a href="#home">Home</a></li>
        <?php endif; ?>
        </li>
            <li><a href="#services">Services</a></li>
        <!-- <li><a href="#about">About</a></li> -->

          <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user'): ?>
            <li><a href="#about">About</a></li>
        <?php endif; ?>

        
        <li><a href="#career">Careers</a></li>

         <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user'): ?>
            <li><a href="#contact">Contact</a></li>
        <?php endif; ?>

               <!-- 🛒 Products and Cart Links -->
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
            <li><a href="products_manage.php">Manage Products</a></li>
        <?php else: ?>
            <!-- <li><a href="products.php">Products</a></li>
            <li><a href="cart.php">Cart</a></li> -->
            <li><a href="products.php">Products</a></li>
        <li>
            <a href="cart.php" style="position: relative;">
                <i class="fa fa-shopping-cart"></i> Cart
                <?php if ($cartCount > 0): ?>
                    <span style="
                        position:absolute;
                        top:-6px;
                        right:-12px;
                        background:#e63946;
                        color:#fff;
                        font-size:12px;
                        font-weight:600;
                        border-radius:50%;
                        padding:3px 7px;
                    ">
                        <?= $cartCount ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>

        <?php endif; ?>
        <!-- <li class="theme-toggle">
            <button id="theme-toggle" class="nav-btn">🌙 Dark Mode</button>
        </li> -->
        <?php if(isset($_SESSION['user_name'])): ?>
        <li class="user-dropdown">
            <button><?= htmlspecialchars($_SESSION['user_name']); ?></button>
            <div class="user-dropdown-content">
                <a href="logout.php">Sign Out</a>
            </div>
        </li>
        <?php else: ?>
        <li><a href="login.php" class="btn-nav">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="main-wrapper">

    <?php include 'Views/Home.html'; ?>
      <section id="services">
    <?php
        if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            include 'services_manage.php';
        } else {
            include 'Views/Services.php';
        }
        ?>
    </section>

    <?php
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user') {
        include 'Views/About.html';
    }
    ?>


<!-- career Section -->
<section id="career">
    <?php
    if (!empty($_SESSION['user_type'])) {
        if ($_SESSION['user_type'] === 'admin') {
            include __DIR__ . '/career_create.php';
        } elseif ($_SESSION['user_type'] === 'user') {
            include __DIR__ . '/career_view.php';
        }
    } else {
        echo "<p style='text-align:center; color:#fff;'>Please login to view career opportunities.</p>";
    }
    ?>
</section>

<?php
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user') {
    include 'Views/Contact.html';

    // Testimonials section for users only
    ?>
    <!-- Testimonials -->
    <section class="testimonials page-header">
        <h2 data-aos="fade-up">What Our Clients Say</h2>
        <div class="swiper mySwiper" data-aos="fade-up" data-aos-delay="200">
            <div class="swiper-wrapper">
                <div class="swiper-slide card">"Amazing service!" - Client A</div>
                <div class="swiper-slide card">"Very professional!" - Client B</div>
                <div class="swiper-slide card">"Highly recommend!" - Client C</div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>
    <?php
}
?>

</div>

<footer>
    <p>&copy; 2025 CSOFT HEALTHCARE SOLUTIONS</p>
    <div class="social">
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script>
AOS.init();
var swiper = new Swiper(".mySwiper", {
    loop: true,
    autoplay: { delay: 3000 },
    pagination: { el: ".swiper-pagination", clickable: true },
});
</script>
</body>
</html>
