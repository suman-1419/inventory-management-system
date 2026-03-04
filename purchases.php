<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DELETE purchase
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Get purchase details to deduct stock back
    $purchase = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM purchases WHERE id=$id"));
    if ($purchase) {
        // Deduct stock back
        mysqli_query($conn, "UPDATE products SET quantity = quantity - {$purchase['quantity']} WHERE id = {$purchase['product_id']}");
        mysqli_query($conn, "DELETE FROM purchases WHERE id=$id");
    }
    header("Location: purchases.php?msg=deleted");
    exit();
}

// Fetch all purchases
$purchases = mysqli_query($conn, "
    SELECT pu.*, p.name as product_name, s.name as supplier_name
    FROM purchases pu
    JOIN products p ON pu.product_id = p.id
    LEFT JOIN suppliers s ON pu.supplier_id = s.id
    ORDER BY pu.id DESC
");

// Fetch products for dropdown
$products  = mysqli_query($conn, "SELECT * FROM products ORDER BY name");

// Fetch suppliers for dropdown
$suppliers = mysqli_query($conn, "SELECT * FROM suppliers ORDER BY name");

// Stats
$totalPurchases  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_cost) as total FROM purchases"))['total'] ?? 0;
$todayPurchases  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_cost) as total FROM purchases WHERE purchase_date = CURDATE()"))['total'] ?? 0;
$totalOrders     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM purchases"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Purchases — InvenTrack</title>
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
    .form-control, .form-select { border-radius:10px; border:1.5px solid #e2e8f0;
      font-size:0.88rem; padding: 9px 12px; }
    .form-control:focus, .form-select:focus { border-color:#4361ee;
      box-shadow:0 0 0 3px rgba(67,97,238,0.15); }
    .page-body { padding: 24px 28px; }
    .stat-card { background:#fff; border-radius:16px; padding:20px;
      box-shadow:0 2px 12px rgba(0,0,0,0.06); }
    .stat-icon { width:44px; height:44px; border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      font-size:1.2rem; margin-bottom:12px; }
    .stat-value { font-size:1.6rem; font-weight:700; color:#1a1f36; }
    .stat-label { font-size:0.78rem; color:#8492a6; font-weight:500; }
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
    <a href="suppliers.php"  class="nav-link"><i class="bi bi-truck"></i> Suppliers</a>
  </nav>
  <div class="nav-label">Transactions</div>
  <nav class="nav flex-column">
    <a href="purchases.php"    class="nav-link active"><i class="bi bi-cart-plus"></i> Purchases</a>
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
      <h6>Purchases</h6>
      <nav><ol class="breadcrumb">
        <li class="breadcrumb-item text-muted">Home</li>
        <li class="breadcrumb-item active">Purchases</li>
      </ol></nav>
    </div>
    <span class="text-muted" style="font-size:0.8rem;"><?= date('D, d M Y') ?></span>
  </div>

  <div class="page-body">

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff4e5; color:#f77f00;">
            <i class="bi bi-cart4"></i>
          </div>
          <div class="stat-value">₹<?= number_format($totalPurchases, 2) ?></div>
          <div class="stat-label">Total Purchase Cost</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon" style="background:#eef0ff; color:#4361ee;">
            <i class="bi bi-calendar-check"></i>
          </div>
          <div class="stat-value">₹<?= number_format($todayPurchases, 2) ?></div>
          <div class="stat-label">Today's Purchases</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon" style="background:#e8faf0; color:#2dc653;">
            <i class="bi bi-bag-check"></i>
          </div>
          <div class="stat-value"><?= $totalOrders ?></div>
          <div class="stat-label">Total Purchase Orders</div>
        </div>
      </div>
    </div>

    <!-- Message -->
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success alert-dismissible fade show"
           style="border-radius:12px; font-size:0.85rem;">
        <?php if ($_GET['msg'] == 'added')   echo '✅ Purchase recorded and stock updated!'; ?>
        <?php if ($_GET['msg'] == 'deleted') echo '🗑️ Purchase deleted and stock adjusted!'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Purchases Table -->
    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="card-title">🛒 All Purchases</div>
        <button class="btn-add btn" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
          <i class="bi bi-plus-lg me-1"></i> Record Purchase
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Product</th>
              <th>Supplier</th>
              <th>Quantity</th>
              <th>Total Cost</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($purchases) > 0): $i = 1;
              while ($p = mysqli_fetch_assoc($purchases)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($p['product_name']) ?></strong></td>
              <td><?= htmlspecialchars($p['supplier_name'] ?? 'N/A') ?></td>
              <td><?= $p['quantity'] ?></td>
              <td><strong style="color:#f77f00;">₹<?= number_format($p['total_cost'], 2) ?></strong></td>
              <td><?= date('d M Y', strtotime($p['purchase_date'])) ?></td>
              <td>
                <a href="purchases.php?delete=<?= $p['id'] ?>"
                   class="btn btn-sm btn-danger"
                   style="border-radius:8px; font-size:0.75rem;"
                   onclick="return confirm('Delete this purchase? Stock will be adjusted.')">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-cart fs-2"></i><br>No purchases recorded yet!
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ADD PURCHASE MODAL -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="purchase_action.php">
        <input type="hidden" name="action" value="add"/>
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-plus-circle me-2 text-warning"></i>Record New Purchase
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Product</label>
            <select name="product_id" class="form-select" required>
              <option value="">-- Select Product --</option>
              <?php
                $products2 = mysqli_query($conn, "SELECT * FROM products ORDER BY name");
                while ($p = mysqli_fetch_assoc($products2)):
              ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['name']) ?> (Current Stock: <?= $p['quantity'] ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Supplier <span class="text-muted">(optional)</span></label>
            <select name="supplier_id" class="form-select">
              <option value="">-- Select Supplier --</option>
              <?php
                $suppliers2 = mysqli_query($conn, "SELECT * FROM suppliers ORDER BY name");
                while ($s = mysqli_fetch_assoc($suppliers2)):
              ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" id="purchaseQty"
                     class="form-control" placeholder="0" min="1" required
                     onchange="calcPurchaseTotal()"/>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Unit Cost (₹)</label>
              <input type="number" name="unit_cost" id="purchaseUnitCost"
                     class="form-control" placeholder="0.00" min="0" step="0.01" required
                     onchange="calcPurchaseTotal()"/>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Total Cost (₹)</label>
            <input type="number" name="total_cost" id="purchaseTotalCost"
                   class="form-control" placeholder="0.00" step="0.01" required readonly
                   style="background:#f8f9fa; font-weight:600; color:#f77f00;"/>
          </div>
          <div class="mb-3">
            <label class="form-label">Purchase Date</label>
            <input type="date" name="purchase_date" class="form-control"
                   value="<?= date('Y-m-d') ?>" required/>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm"
                  data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm"
                  style="border-radius:8px; color:#fff;">
            <i class="bi bi-check-lg me-1"></i>Record Purchase
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function calcPurchaseTotal() {
  const qty  = parseFloat(document.getElementById('purchaseQty').value) || 0;
  const cost = parseFloat(document.getElementById('purchaseUnitCost').value) || 0;
  document.getElementById('purchaseTotalCost').value = (qty * cost).toFixed(2);
}
</script>
</body>
</html>