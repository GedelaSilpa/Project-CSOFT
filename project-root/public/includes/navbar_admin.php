<?php
// ✅ Admin Navbar for CSOFT
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav style="background:#000080; padding:10px 30px; display:flex; align-items:center; justify-content:space-between;">
    <div>
        <a href="dashboard.php" style="color:white; font-size:22px; text-decoration:none; font-weight:600;">
            CSOFT Admin Panel
        </a>
    </div>
    <ul style="list-style:none; display:flex; gap:20px; margin:0; padding:0;">
        <li><a href="products_manage.php" style="color:white; text-decoration:none; font-weight:500;">Manage Products</a></li>
        <li><a href="product_form.php" style="color:white; text-decoration:none; font-weight:500;">Add Product</a></li>
        <li><a href="home.php" style="color:white; text-decoration:none; font-weight:500;">User Site</a></li>
        <li><a href="logout.php" style="color:white; text-decoration:none; font-weight:500;">Logout</a></li>
    </ul>
</nav>
