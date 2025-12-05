<?php
require_once __DIR__ . '/../app/auth.php';
require_auth('admin');
require_once __DIR__ . '/../app/db.php';

// ✅ Adjusted include paths
$includePath = __DIR__ . '/includes/';
if (!is_dir($includePath)) {
    // if includes folder is one level above public/
    $includePath = __DIR__ . '/../includes/';
}

$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Navbar include (path-safe)
$navbarFile = $includePath . 'navbar_admin.php';
if (file_exists($navbarFile)) {
    include $navbarFile;
}
?>

<!-- ✅ CSS (Unique Scoped Classes) -->
<style>
body.admin-products-page {
  font-family: "Poppins", sans-serif;
  background-color: #f4f6f9;
  margin: 0;
  padding: 0;
}

/* ✅ Page Wrapper */
.admin-products-wrapper {
  max-width: 1200px;
  margin: 40px auto;
  background: #ffffff;
  padding: 30px 40px;
  border-radius: 12px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

/* ✅ Page Heading */
.admin-products-title {
  text-align: center;
  font-size: 2rem;
  font-weight: 600;
  color: #003366;
  margin-bottom: 25px;
}

/* ✅ Add Button */
.admin-products-add-btn {
  display: inline-block;
  background: #003366;
  color: white;
  text-decoration: none;
  padding: 10px 22px;
  border-radius: 30px;
  font-weight: 500;
  transition: 0.3s;
}
.admin-products-add-btn:hover {
  background: #001f4d;
}

/* ✅ Data Table */
.admin-products-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 25px;
  font-size: 0.95rem;
}
.admin-products-table th,
.admin-products-table td {
  text-align: center;
  padding: 12px;
  border-bottom: 1px solid #e2e8f0;
}
.admin-products-table thead {
  background: #003366;
  color: white;
}
.admin-products-table tr:hover {
  background-color: #f9fbfd;
  transition: 0.2s;
}

/* ✅ Buttons (Edit / Delete) */
.admin-products-btn-edit,
.admin-products-btn-delete {
  padding: 6px 14px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  transition: 0.3s;
}
.admin-products-btn-edit {
  background: #28a745;
  color: #fff;
}
.admin-products-btn-edit:hover {
  background: #218838;
}
.admin-products-btn-delete {
  background: #dc3545;
  color: #fff;
}
.admin-products-btn-delete:hover {
  background: #c82333;
}

/* ✅ Responsive */
@media (max-width: 768px) {
  .admin-products-wrapper {
    padding: 20px;
  }
  .admin-products-table th,
  .admin-products-table td {
    padding: 10px;
    font-size: 0.9rem;
  }
}
</style>

<!-- ✅ Page HTML -->
<body class="admin-products-page">
<div class="admin-products-wrapper">
    <h1 class="admin-products-title">Manage Products</h1>
    <a href="product_form.php" class="admin-products-add-btn">+ Add New Product</a>

    <table class="admin-products-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price (₹)</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td>
                    <img src="<?= htmlspecialchars($p['image']) ?>" width="60" height="60" style="object-fit:cover; border-radius:8px;">
                </td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['category']) ?></td>
                <td><strong style="color:#28a745;">₹<?= number_format($p['price'], 2) ?></strong></td>
                <td><?= $p['stock'] ?></td>
                <td>
                    <span style="color:<?= $p['is_active'] == 'Y' ? '#2ecc71' : '#e74c3c' ?>;">
                        <?= $p['is_active'] == 'Y' ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td>
                    <a href="product_form.php?id=<?= $p['id'] ?>" class="admin-products-btn-edit">Edit</a>
                    <a href="product_delete.php?id=<?= $p['id'] ?>" class="admin-products-btn-delete" onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$footerFile = $includePath . 'footer.php';
if (file_exists($footerFile)) {
    include $footerFile;
}
?>
</body>
