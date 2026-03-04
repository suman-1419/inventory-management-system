<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DELETE supplier
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM suppliers WHERE id=$id");
    header("Location: suppliers.php?msg=deleted");
    exit();
}

// Fetch all suppliers
$suppliers = mysqli_query($conn, "SELECT * FROM suppliers ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Suppliers — InvenTrack</title>
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
    .supplier-avatar {
      width: 38px; height: 38px; border-radius: 10px;
      background: linear-gradient(135deg, #e8f4ff, #d0e8ff);
      display: flex; align-items: center; justify-content: center;
      color: #4361ee; font-weight: 700; font-size: 0.9rem;
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
    <a href="categories.php" class="nav-link"><i class="bi bi-tags"></i> Categories</a>
    <a href="suppliers.php"  class="nav-link active"><i class="bi bi-truck"></i> Suppliers</a>
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
      <h6>Suppliers</h6>
      <nav><ol class="breadcrumb">
        <li class="breadcrumb-item text-muted">Home</li>
        <li class="breadcrumb-item active">Suppliers</li>
      </ol></nav>
    </div>
    <span class="text-muted" style="font-size:0.8rem;"><?= date('D, d M Y') ?></span>
  </div>

  <div class="page-body">

    <!-- Message -->
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success alert-dismissible fade show"
           style="border-radius:12px; font-size:0.85rem;">
        <?php if ($_GET['msg'] == 'added')   echo '✅ Supplier added successfully!'; ?>
        <?php if ($_GET['msg'] == 'updated') echo '✅ Supplier updated successfully!'; ?>
        <?php if ($_GET['msg'] == 'deleted') echo '🗑️ Supplier deleted successfully!'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Suppliers Table -->
    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="card-title">🚚 All Suppliers</div>
        <button class="btn-add btn" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
          <i class="bi bi-plus-lg me-1"></i> Add Supplier
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Supplier Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($suppliers) > 0): $i = 1;
              while ($s = mysqli_fetch_assoc($suppliers)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="supplier-avatar">
                    <?= strtoupper(substr($s['name'], 0, 2)) ?>
                  </div>
                  <strong><?= htmlspecialchars($s['name']) ?></strong>
                </div>
              </td>
              <td>
                <a href="mailto:<?= htmlspecialchars($s['email']) ?>"
                   style="color:#4361ee; text-decoration:none;">
                  <?= htmlspecialchars($s['email'] ?? '—') ?>
                </a>
              </td>
              <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
              <td><?= htmlspecialchars($s['address'] ?? '—') ?></td>
              <td>
                <button class="btn btn-sm btn-warning me-1"
                  style="border-radius:8px; font-size:0.75rem;"
                  onclick="editSupplier(
                    <?= $s['id'] ?>,
                    '<?= addslashes($s['name']) ?>',
                    '<?= addslashes($s['email'] ?? '') ?>',
                    '<?= addslashes($s['phone'] ?? '') ?>',
                    '<?= addslashes($s['address'] ?? '') ?>'
                  )">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <a href="suppliers.php?delete=<?= $s['id'] ?>"
                   class="btn btn-sm btn-danger"
                   style="border-radius:8px; font-size:0.75rem;"
                   onclick="return confirm('Are you sure you want to delete this supplier?')">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                <i class="bi bi-truck fs-2"></i><br>No suppliers found. Add your first supplier!
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ADD SUPPLIER MODAL -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="supplier_action.php">
        <input type="hidden" name="action" value="add"/>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Supplier</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Supplier Name</label>
            <input type="text" name="name" class="form-control"
                   placeholder="e.g. ABC Traders" required/>
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-muted">(optional)</span></label>
            <input type="email" name="email" class="form-control"
                   placeholder="supplier@email.com"/>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" name="phone" class="form-control"
                   placeholder="e.g. 9876543210"/>
          </div>
          <div class="mb-3">
            <label class="form-label">Address <span class="text-muted">(optional)</span></label>
            <textarea name="address" class="form-control" rows="2"
                      placeholder="Supplier address..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;">
            <i class="bi bi-plus-lg me-1"></i>Add Supplier
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT SUPPLIER MODAL -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="supplier_action.php">
        <input type="hidden" name="action" value="edit"/>
        <input type="hidden" name="id" id="editId"/>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil me-2 text-warning"></i>Edit Supplier</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Supplier Name</label>
            <input type="text" name="name" id="editName" class="form-control" required/>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="editEmail" class="form-control"/>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="editPhone" class="form-control"/>
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" id="editAddress" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm" style="border-radius:8px;">
            <i class="bi bi-save me-1"></i>Update Supplier
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editSupplier(id, name, email, phone, address) {
  document.getElementById('editId').value      = id;
  document.getElementById('editName').value    = name;
  document.getElementById('editEmail').value   = email;
  document.getElementById('editPhone').value   = phone;
  document.getElementById('editAddress').value = address;
  new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
}
</script>
</body>
</html>