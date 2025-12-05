<?php
require_once __DIR__ . '/../app/auth.php';
require_auth('admin');
require_once __DIR__ . '/../app/db.php';

$id = $_GET['id'] ?? null;
$product = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    $is_active = $_POST['is_active'];
    $image = $product['image'] ?? '';

    if (!empty($_FILES['image']['name'])) {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/products/" . $image);
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=?, image=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $desc, $price, $category, $stock, $image, $is_active, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, stock, image, is_active, created_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $desc, $price, $category, $stock, $image, $is_active, $_SESSION['username']]);
    }

    header("Location: products_manage.php");
    exit;
}
?>
<?php include 'includes/navbar_admin.php'; ?>

<div class="form-container">
    <h2><?= $id ? 'Edit Product' : 'Add Product' ?></h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Name</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($product['name'] ?? '') ?>">

        <label>Description</label>
        <textarea name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

        <label>Price (₹)</label>
        <input type="number" name="price" step="0.01" required value="<?= htmlspecialchars($product['price'] ?? '') ?>">

        <label>Category</label>
        <input type="text" name="category" value="<?= htmlspecialchars($product['category'] ?? '') ?>">

        <label>Stock</label>
        <input type="number" name="stock" value="<?= htmlspecialchars($product['stock'] ?? '') ?>">

        <label>Status</label>
        <select name="is_active">
            <option value="Y" <?= ($product['is_active'] ?? '') == 'Y' ? 'selected' : '' ?>>Active</option>
            <option value="N" <?= ($product['is_active'] ?? '') == 'N' ? 'selected' : '' ?>>Inactive</option>
        </select>

<label>Image</label>
<input type="file" name="image">

<?php if (!empty($product['image'])): ?>
    <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" 
         width="100" height="100" 
         style="object-fit:cover; border-radius:8px;">
<?php endif; ?>


        <button type="submit" class="btn btn-success"><?= $id ? 'Update' : 'Add' ?></button>
        <a href="products_manage.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<style>
    /* ✅ Product form */
.form-container {
  max-width: 600px;
  margin: 50px auto;
  background: #fff;
  padding: 35px 40px;
  border-radius: 15px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
}
.form-container h2 {
  text-align: center;
  color: navy;
  margin-bottom: 25px;
  font-weight: 600;
}
.form-container label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #333;
}
.form-container input[type="text"],
.form-container input[type="number"],
.form-container input[type="file"],
.form-container textarea,
.form-container select {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  margin-bottom: 15px;
  font-size: 1rem;
  box-sizing: border-box;
  transition: border 0.2s;
}
.form-container input:focus,
.form-container textarea:focus,
.form-container select:focus {
  border-color: navy;
  outline: none;
}
.form-container textarea {
  min-height: 100px;
  resize: vertical;
}
.form-container img {
  display: block;
  margin-top: 8px;
  border-radius: 10px;
}

/* ✅ Form buttons */
.btn-success,
.btn-secondary {
  display: inline-block;
  text-align: center;
  padding: 10px 20px;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 500;
  transition: 0.3s;
  cursor: pointer;
}
.btn-success {
  background: linear-gradient(135deg, #28a745, #218838);
  color: white;
  border: none;
}
.btn-success:hover {
  background: linear-gradient(135deg, #218838, #1e7e34);
}
.btn-secondary {
  background: #6c757d;
  color: white;
  margin-left: 10px;
}
.btn-secondary:hover {
  background: #5a6268;
}

/* ✅ Responsive layout */
@media (max-width: 768px) {
  .container {
    padding: 20px;
  }
  .data-table th,
  .data-table td {
    padding: 10px;
    font-size: 0.9rem;
  }
  .form-container {
    padding: 25px;
  }
}
</style>