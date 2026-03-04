<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DELETE sale
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Get sale details to restore stock
    $sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sales WHERE id=$id"));
    if ($sale) {
        // Restore stock
        mysqli_query($conn, "UPDATE products SET quantity = quantity + {$sale['quantity']} WHERE id = {$sale['product_id']}");
        mysqli_query($conn, "DELETE FROM sales WHERE id=$id");
    }
    header("Location: sales.php?msg=deleted");
    exit();
}

// Fetch all sales
$sales = mysqli_query($conn, "
    SELECT s.*, p.name as product_name
    FROM sales s
    JOIN products p ON s.product_id = p.id
    ORDER BY s.id DESC
");

// Fetch products for dropdown
$products = mysqli_query($conn, "SELECT * FROM products WHERE quantity > 0 ORDER BY name");

// Total sales amount
$totalSales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as total FROM sales"))['total'] ?? 0;
$todaySales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as total FROM sales WHERE sale_date = CURDATE()"))['total'] ?? 0;
$totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM sales"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sales — InvenTrack</title>
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
      display:flex; align-items:center; justify-content:center; font-size:1.2rem; margin-bottom:12px; }
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
    <a href="purchases.php"    class="nav-link"><i class="bi bi-cart-plus"></i> Purchases</a>
    <a href="sales.php"        class="nav-link active"><i class="bi bi-receipt"></i> Sales</a>
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
      <h6>Sales</h6>
      <nav><ol class="breadcrumb">
        <li class="breadcrumb-item text-muted">Home</li>
        <li class="breadcrumb-item active">Sales</li>
      </ol></nav>
    </div>
    <span class="text-muted" style="font-size:0.8rem;"><?= date('D, d M Y') ?></span>
  </div>

  <div class="page-body">

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon" style="background:#e8faf0; color:#2dc653;">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="stat-value">₹<?= number_format($totalSales, 2) ?></div>
          <div class="stat-label">Total Sales Revenue</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon" style="background:#eef0ff; color:#4361ee;">
            <i class="bi bi-calendar-check"></i>
          </div>
          <div class="stat-value">₹<?= number_format($todaySales, 2) ?></div>
          <div class="stat-label">Today's Sales</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff4e5; color:#f77f00;">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="stat-value"><?= $totalOrders ?></div>
          <div class="stat-label">Total Orders</div>
        </div>
      </div>
    </div>

    <!-- Message -->
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success alert-dismissible fade show"
           style="border-radius:12px; font-size:0.85rem;">
        <?php if ($_GET['msg'] == 'added')   echo '✅ Sale recorded successfully!'; ?>
        <?php if ($_GET['msg'] == 'deleted') echo '🗑️ Sale deleted and stock restored!'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Sales Table -->
    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="card-title">🧾 All Sales</div>
        <button class="btn-add btn" data-bs-toggle="modal" data-bs-target="#addSaleModal">
          <i class="bi bi-plus-lg me-1"></i> Record Sale
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Product</th>
              <th>Customer</th>
              <th>Quantity</th>
              <th>Total Amount</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($sales) > 0): $i = 1;
              while ($s = mysqli_fetch_assoc($sales)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($s['product_name']) ?></strong></td>
              <td><?= htmlspecialchars($s['customer_name'] ?? 'Walk-in') ?></td>
              <td><?= $s['quantity'] ?></td>
              <td><strong style="color:#2dc653;">₹<?= number_format($s['total_price'], 2) ?></strong></td>
              <td><?= date('d M Y', strtotime($s['sale_date'])) ?></td>
              <td>
                <a href="sales.php?delete=<?= $s['id'] ?>"
                   class="btn btn-sm btn-danger"
                   style="border-radius:8px; font-size:0.75rem;"
                   onclick="return confirm('Delete this sale? Stock will be restored.')">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-receipt fs-2"></i><br>No sales recorded yet!
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ADD SALE MODAL -->
<div class="modal fade" id="addSaleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="sale_action.php">
        <input type="hidden" name="action" value="add"/>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-success"></i>Record New Sale</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Product</label>
            <select name="product_id" id="productSelect" class="form-select" required
                    onchange="updatePrice()">
              <option value="">-- Select Product --</option>
              <?php while ($p = mysqli_fetch_assoc($products)): ?>
                <option value="<?= $p['id'] ?>"
                        data-price="<?= $p['price'] ?>"
                        data-stock="<?= $p['quantity'] ?>">
                  <?= htmlspecialchars($p['name']) ?> (Stock: <?= $p['quantity'] ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Customer Name <span class="text-muted">(optional)</span></label>
            <input type="text" name="customer_name" class="form-control"
                   placeholder="e.g. Rahul Sharma"/>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" id="quantityInput" class="form-control"
                     placeholder="0" min="1" required onchange="calculateTotal()"/>
              <small id="stockInfo" class="text-muted"></small>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Unit Price (₹)</label>
              <input type="number" name="unit_price" id="unitPrice" class="form-control"
                     placeholder="0.00" min="0" step="0.01" required onchange="calculateTotal()"/>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Total Amount (₹)</label>
            <input type="number" name="total_price" id="totalPrice" class="form-control"
                   placeholder="0.00" step="0.01" required readonly
                   style="background:#f8f9fa; font-weight:600; color:#2dc653;"/>
          </div>
          <div class="mb-3">
            <label class="form-label">Sale Date</label>
            <input type="date" name="sale_date" class="form-control"
                   value="<?= date('Y-m-d') ?>" required/>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm" style="border-radius:8px;">
            <i class="bi bi-check-lg me-1"></i>Record Sale
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updatePrice() {
  const select   = document.getElementById('productSelect');
  const selected = select.options[select.selectedIndex];
  const price    = selected.getAttribute('data-price');
  const stock    = selected.getAttribute('data-stock');

  document.getElementById('unitPrice').value     = price || '';
  document.getElementById('stockInfo').innerText = stock ? 'Available: ' + stock + ' units' : '';
  document.getElementById('quantityInput').max   = stock || '';
  calculateTotal();
}

function calculateTotal() {
  const qty   = parseFloat(document.getElementById('quantityInput').value) || 0;
  const price = parseFloat(document.getElementById('unitPrice').value) || 0;
  document.getElementById('totalPrice').value = (qty * price).toFixed(2);
}
</script>
</body>
</html>