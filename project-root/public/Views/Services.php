<?php
require_once __DIR__ . '/../../app/db.php';

$services = $pdo->query("SELECT * FROM services ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device‑width, initial-scale=1.0">
  <title>Our Services</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<!-- Services Section -->
<section id="services" class="services-page container">
  <h1>Our Services</h1>
  <div class="slider-wrapper">
    <button class="nav-button nav-prev">&lt;</button>
    <div class="services-carousel" id="carousel">
      <?php foreach ($services as $service): ?>
        <div class="service-card">
          <i class="<?= htmlspecialchars($service['icon']) ?>"></i>
          <h3><?= htmlspecialchars($service['title']) ?></h3>
          <p><?= htmlspecialchars($service['description']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="nav-button nav-next">&gt;</button>
  </div>
</section>

  <script>
    (function(){
      const carousel = document.getElementById('carousel');
      const btnNext = document.querySelector('.nav-next');
      const btnPrev = document.querySelector('.nav-prev');

      const scrollAmount = 300;  // how many px to scroll per click — adjust as needed

      btnNext.addEventListener('click', () => {
        carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
      });
      btnPrev.addEventListener('click', () => {
        carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
      });

      // Optional: hide prev/next when at ends
      function updateButtons() {
        if (carousel.scrollLeft <= 0) {
          btnPrev.style.visibility = 'hidden';
        } else {
          btnPrev.style.visibility = 'visible';
        }
        if (carousel.scrollLeft + carousel.clientWidth >= carousel.scrollWidth) {
          btnNext.style.visibility = 'hidden';
        } else {
          btnNext.style.visibility = 'visible';
        }
      }
      carousel.addEventListener('scroll', updateButtons);
      window.addEventListener('resize', updateButtons);
      updateButtons();
    })();
  </script>
</body>
</html>
