<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DELETE product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM products WHERE id=$id");
    header("Location: products.php?msg=deleted");
    exit();
}

// Fetch all products with category name
$products = mysqli_query($conn, "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
");

// Fetch categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Products — InvenTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    * { font-family: 'Inter', sans-serif; }
    body { background: #f0f2f5; }

    /* SIDEBAR */
    .sidebar {
      width: 250px; min-height: 100vh;
      background: linear-gradient(160deg, #1a1f36 0%, #2d3561 100%);
      position: fixed; top: 0; left: 0; z-index: 100;
    }
    .sidebar-brand { padding: 22px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
    .sidebar-brand h5 { color: #fff; font-weight: 700; margin: 0; }
    .sidebar-brand span { color: #7b93ff; }
    .nav-label { color: rgba(255,255,255,0.35); font-size: 0.7rem; font-weight: 600;
      letter-spacing: 1.2px; text-transform: uppercase; padding: 18px 20px 6px; }
    .sidebar .nav-link { color: rgba(255,255,255,0.65); padding: 10px 20px;
      border-radius: 8px; margin: 2px 10px; font-size: 0.88rem; font-weight: 500;
      display: flex; align-items: center; gap: 10px; transition: all 0.2s; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active {
      background: rgba(123,147,255,0.18); color: #fff; }
    .sidebar .nav-link.active { border-left: 3px solid #7b93ff; }

    /* MAIN */
    .main-content { margin-left: 250px; }
    .topbar { background: #fff; padding: 14px 28px; display: flex;
      align-items: center; justify-content: space-between;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top:0; z-index:90; }
    .topbar h6 { font-weight: 600; color: #1a1f36; margin:0; }
    .topbar .breadcrumb { margin:0; font-size:0.78rem; }

    /* CONTENT CARD */
    .content-card { background:#fff; border-radius:16px; padding:22px;
      box-shadow:0 2px 12px rgba(0,0,0,0.06); }
    .content-card .card-title { font-weight:700; font-size:0.95rem; color:#1a1f36; }
    .table th { font-size:0.75rem; font-weight:600; color:#8492a6;
      text-transform:uppercase; letter-spacing:0.6px; border-bottom: 2px solid #f0f2f5; }
    .table td { font-size:0.85rem; color:#3d4557; vertical-align:middle; }
    .table tr:hover { background: #f8f9ff; }

    /* BUTTONS */
    .btn-add { background: linear-gradient(135deg,#4361ee,#5563eb);
      color:#fff; border:none; border-radius:10px; padding:9px 18px;
      font-size:0.85rem; font-weight:600; }
    .btn-add:hover { background: linear-gradient(135deg,#3451d1,#4452d8); color:#fff; }

    /* MODAL */
    .modal-content { border-radius:16px; border:none; }
    .modal-header { border-bottom: 1px solid #f0f2f5; padding: 20px 24px; }
    .modal-title { font-weight:700; font-size:1rem; color:#1a1f36; }
    .modal-body { padding: 20px 24px; }
    .modal-footer { border-top: 1px solid #f0f2f5; padding: 16px 24px; }
    .form-label { font-size:0.82rem; font-weight:600; color:#3d4557; }
    .form-control, .form-select { border-radius:10px; border:1.5px solid #e2e8f0;
      font-size:0.88rem; padding: 9px 12px; }
    .form-control:focus, .form-select:focus { border-color:#4361ee;
      box-shadow:0 0 0 3px rgba(67,97,238,0.15); }

    .badge-stock { font-size:0.72rem; padding:4px 10px; border-radius:20px; }
    .page-body { padding: 24px 28px; }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-brand">
    <h5>Inven<span>Track</span></h5>
  </div>
  <div class="nav-label">Main Menu</div>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
    <a href="products.php"  class="nav-link active"><i class="bi bi-box-seam"></i> Products</a>
    <a href="categories.php" class="nav-link"><i class="bi bi-tags"></i> Categories</a>
    <a href="suppliers.php"  class="nav-link"><i class="bi bi-truck"></i> Suppliers</a>
  </nav>
  <div class="nav-label">Transactions</div>
  <nav class="nav flex-column">
    <a href="purchases.php"    class="nav-link"><i class="bi bi-cart-plus"></i> Purchases</a>
    <a href="sales.php"        class="nav-link"><i class="bi bi-receipt"></i> Sales</a>
    <a href="stock_alerts.php" class="nav-link"><i class="bi bi-exclamation-triangle"></i> Stock Alerts</a>
  </nav>
  <div class="nav-label">Reports</div>
  <nav class="nav flex-column">
    <a href="reports.php" class="nav-link"><i class="bi bi-bar-chart-line"></i> Reports</a>
  </nav>
  <div class="nav-label">Account</div>
  <nav class="nav flex-column">
    <a href="logout.php" class="nav-link" style="color:#e63946 !important;">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </nav>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

  <!-- TOPBAR -->
  <div class="topbar">
    <div>
      <h6>Products</h6>
      <nav><ol class="breadcrumb">
        <li class="breadcrumb-item text-muted">Home</li>
        <li class="breadcrumb-item active">Products</li>
      </ol></nav>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="text-muted" style="font-size:0.8rem;"><?= date('D, d M Y') ?></span>
    </div>
  </div>

  <div class="page-body">

    <!-- Success / Delete Message -->
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert"
           style="border-radius:12px; font-size:0.85rem;">
        <?php if ($_GET['msg'] == 'added')   echo '✅ Product added successfully!'; ?>
        <?php if ($_GET['msg'] == 'updated') echo '✅ Product updated successfully!'; ?>
        <?php if ($_GET['msg'] == 'deleted') echo '🗑️ Product deleted successfully!'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Products Table Card -->
    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="card-title">📦 All Products</div>
        <button class="btn-add btn" data-bs-toggle="modal" data-bs-target="#addProductModal">
          <i class="bi bi-plus-lg me-1"></i> Add Product
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Product Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Price</th>
              <th>Reorder Level</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($products) > 0): $i = 1;
              while ($p = mysqli_fetch_assoc($products)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
              <td><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></td>
              <td><?= $p['quantity'] ?></td>
              <td>₹<?= number_format($p['price'], 2) ?></td>
              <td><?= $p['reorder_level'] ?></td>
              <td>
                <?php if ($p['quantity'] <= $p['reorder_level']): ?>
                  <span class="badge bg-danger bg-opacity-10 text-danger badge-stock">Low Stock</span>
                <?php else: ?>
                  <span class="badge bg-success bg-opacity-10 text-success badge-stock">In Stock</span>
                <?php endif; ?>
              </td>
              <td>
                <!-- Edit Button -->
                <button class="btn btn-sm btn-warning me-1"
                  style="border-radius:8px; font-size:0.75rem;"
                  onclick="editProduct(
                    <?= $p['id'] ?>,
                    '<?= addslashes($p['name']) ?>',
                    <?= $p['category_id'] ?>,
                    <?= $p['quantity'] ?>,
                    <?= $p['price'] ?>,
                    <?= $p['reorder_level'] ?>
                  )">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <!-- Delete Button -->
                <a href="products.php?delete=<?= $p['id'] ?>"
                   class="btn btn-sm btn-danger"
                   style="border-radius:8px; font-size:0.75rem;"
                   onclick="return confirm('Are you sure you want to delete this product?')">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-2"></i><br>No products found. Add your first product!
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ══ ADD PRODUCT MODAL ══ -->
<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="product_action.php">
        <input type="hidden" name="action" value="add"/>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter product name" required/>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
              <option value="">-- Select Category --</option>
              <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" class="form-control" placeholder="0" min="0" required/>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Price (₹)</label>
              <input type="number" name="price" class="form-control" placeholder="0.00" min="0" step="0.01" required/>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Reorder Level</label>
            <input type="number" name="reorder_level" class="form-control" placeholder="10" min="0" required/>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;">
            <i class="bi bi-plus-lg me-1"></i>Add Product
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ══ EDIT PRODUCT MODAL ══ -->
<div class="modal fade" id="editProductModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="product_action.php">
        <input type="hidden" name="action" value="edit"/>
        <input type="hidden" name="id" id="editId"/>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil me-2 text-warning"></i>Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" id="editName" class="form-control" required/>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" id="editCategory" class="form-select" required>
              <?php
                // Re-fetch categories for edit modal
                $cats2 = mysqli_query($conn, "SELECT * FROM categories");
                while ($cat = mysqli_fetch_assoc($cats2)):
              ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" id="editQuantity" class="form-control" min="0" required/>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Price (₹)</label>
              <input type="number" name="price" id="editPrice" class="form-control" min="0" step="0.01" required/>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Reorder Level</label>
            <input type="number" name="reorder_level" id="editReorder" class="form-control" min="0" required/>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm" style="border-radius:8px;">
            <i class="bi bi-save me-1"></i>Update Product
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editProduct(id, name, categoryId, quantity, price, reorderLevel) {
  document.getElementById('editId').value       = id;
  document.getElementById('editName').value     = name;
  document.getElementById('editCategory').value = categoryId;
  document.getElementById('editQuantity').value = quantity;
  document.getElementById('editPrice').value    = price;
  document.getElementById('editReorder').value  = reorderLevel;
  new bootstrap.Modal(document.getElementById('editProductModal')).show();
}
</script>
</body>
</html>