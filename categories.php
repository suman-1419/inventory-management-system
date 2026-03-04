<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DELETE category
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM categories WHERE id=$id");
    header("Location: categories.php?msg=deleted");
    exit();
}

// Fetch all categories with product count
$categories = mysqli_query($conn, "
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Categories — InvenTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    * { font-family: 'Inter', sans-serif; }
    body { background: #f0f2f5; }
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
    .main-content { margin-left: 250px; }
    .topbar { background: #fff; padding: 14px 28px; display: flex;
      align-items: center; justify-content: space-between;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top:0; z-index:90; }
    .topbar h6 { font-weight: 600; color: #1a1f36; margin:0; }
    .topbar .breadcrumb { margin:0; font-size:0.78rem; }
    .content-card { background:#fff; border-radius:16px; padding:22px;
      box-shadow:0 2px 12px rgba(0,0,0,0.06); }
    .content-card .card-title { font-weight:700; font-size:0.95rem; color:#1a1f36; }
    .table th { font-size:0.75rem; font-weight:600; color:#8492a6;
      text-transform:uppercase; letter-spacing:0.6px; border-bottom: 2px solid #f0f2f5; }
    .table td { font-size:0.85rem; color:#3d4557; vertical-align:middle; }
    .table tr:hover { background: #f8f9ff; }
    .btn-add { background: linear-gradient(135deg,#4361ee,#5563eb);
      color:#fff; border:none; border-radius:10px; padding:9px 18px;
      font-size:0.85rem; font-weight:600; }
    .btn-add:hover { background: linear-gradient(135deg,#3451d1,#4452d8); color:#fff; }
    .modal-content { border-radius:16px; border:none; }
    .modal-header { border-bottom: 1px solid #f0f2f5; padding: 20px 24px; }
    .modal-title { font-weight:700; font-size:1rem; color:#1a1f36; }
    .modal-body { padding: 20px 24px; }
    .modal-footer { border-top: 1px solid #f0f2f5; padding: 16px 24px; }
    .form-label { font-size:0.82rem; font-weight:600; color:#3d4557; }
    .form-control { border-radius:10px; border:1.5px solid #e2e8f0;
      font-size:0.88rem; padding: 9px 12px; }
    .form-control:focus { border-color:#4361ee;
      box-shadow:0 0 0 3px rgba(67,97,238,0.15); }
    .page-body { padding: 24px 28px; }

    /* Category color badges */
    .cat-icon {
      width: 36px; height: 36px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem;
    }
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
    <a href="dashboard.php"  class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
    <a href="products.php"   class="nav-link"><i class="bi bi-box-seam"></i> Products</a>
    <a href="categories.php" class="nav-link active"><i class="bi bi-tags"></i> Categories</a>
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
      <h6>Categories</h6>
      <nav><ol class="breadcrumb">
        <li class="breadcrumb-item text-muted">Home</li>
        <li class="breadcrumb-item active">Categories</li>
      </ol></nav>
    </div>
    <span class="text-muted" style="font-size:0.8rem;"><?= date('D, d M Y') ?></span>
  </div>

  <div class="page-body">

    <!-- Message -->
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success alert-dismissible fade show"
           style="border-radius:12px; font-size:0.85rem;">
        <?php if ($_GET['msg'] == 'added')   echo '✅ Category added successfully!'; ?>
        <?php if ($_GET['msg'] == 'updated') echo '✅ Category updated successfully!'; ?>
        <?php if ($_GET['msg'] == 'deleted') echo '🗑️ Category deleted successfully!'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Categories Table -->
    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="card-title">🏷️ All Categories</div>
        <button class="btn-add btn" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
          <i class="bi bi-plus-lg me-1"></i> Add Category
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Category Name</th>
              <th>Description</th>
              <th>Total Products</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($categories) > 0): $i = 1;
              while ($cat = mysqli_fetch_assoc($categories)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="cat-icon" style="background:#eef0ff; color:#4361ee;">
                    <i class="bi bi-tag-fill"></i>
                  </div>
                  <strong><?= htmlspecialchars($cat['name']) ?></strong>
                </div>
              </td>
              <td><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
              <td>
                <span class="badge bg-primary bg-opacity-10 text-primary"
                      style="border-radius:20px; font-size:0.75rem; padding:4px 12px;">
                  <?= $cat['product_count'] ?> Products
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-warning me-1"
                  style="border-radius:8px; font-size:0.75rem;"
                  onclick="editCategory(
                    <?= $cat['id'] ?>,
                    '<?= addslashes($cat['name']) ?>',
                    '<?= addslashes($cat['description'] ?? '') ?>'
                  )">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <a href="categories.php?delete=<?= $cat['id'] ?>"
                   class="btn btn-sm btn-danger"
                   style="border-radius:8px; font-size:0.75rem;"
                   onclick="return confirm('Delete this category? Products in this category will be unlinked.')">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                <i class="bi bi-tags fs-2"></i><br>No categories found!
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ADD CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="category_action.php">
        <input type="hidden" name="action" value="add"/>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input type="text" name="name" class="form-control"
                   placeholder="e.g. Electronics" required/>
          </div>
          <div class="mb-3">
            <label class="form-label">Description <span class="text-muted">(optional)</span></label>
            <textarea name="description" class="form-control" rows="3"
                      placeholder="Brief description..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;">
            <i class="bi bi-plus-lg me-1"></i>Add Category
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT CATEGORY MODAL -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="category_action.php">
        <input type="hidden" name="action" value="edit"/>
        <input type="hidden" name="id" id="editId"/>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil me-2 text-warning"></i>Edit Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input type="text" name="name" id="editName" class="form-control" required/>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm" style="border-radius:8px;">
            <i class="bi bi-save me-1"></i>Update Category
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editCategory(id, name, description) {
  document.getElementById('editId').value          = id;
  document.getElementById('editName').value        = name;
  document.getElementById('editDescription').value = description;
  new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}
</script>
</body>
</html>