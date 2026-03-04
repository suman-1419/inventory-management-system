<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Date filters
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to   = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');

// Sales Report
$salesReport = mysqli_query($conn, "
    SELECT s.*, p.name as product_name
    FROM sales s
    JOIN products p ON s.product_id = p.id
    WHERE s.sale_date BETWEEN '$from' AND '$to'
    ORDER BY s.sale_date DESC
");
$totalSalesAmt = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(total_price) as total FROM sales
     WHERE sale_date BETWEEN '$from' AND '$to'"))['total'] ?? 0;
$totalSalesQty = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(quantity) as total FROM sales
     WHERE sale_date BETWEEN '$from' AND '$to'"))['total'] ?? 0;

// Purchase Report
$purchaseReport = mysqli_query($conn, "
    SELECT pu.*, p.name as product_name, s.name as supplier_name
    FROM purchases pu
    JOIN products p ON pu.product_id = p.id
    LEFT JOIN suppliers s ON pu.supplier_id = s.id
    WHERE pu.purchase_date BETWEEN '$from' AND '$to'
    ORDER BY pu.purchase_date DESC
");
$totalPurchaseAmt = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(total_cost) as total FROM purchases
     WHERE purchase_date BETWEEN '$from' AND '$to'"))['total'] ?? 0;

// Stock Report
$stockReport = mysqli_query($conn, "
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.quantity ASC
");
$lowStockCount = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM products
     WHERE quantity <= reorder_level"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reports — InvenTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    .card-title { font-weight:700; font-size:0.95rem; color:#1a1f36; }
    .table th { font-size:0.75rem; font-weight:600; color:#8492a6;
      text-transform:uppercase; letter-spacing:0.6px; border-bottom: 2px solid #f0f2f5; }
    .table td { font-size:0.85rem; color:#3d4557; vertical-align:middle; }
    .table tr:hover { background: #f8f9ff; }
    .stat-card { background:#fff; border-radius:16px; padding:20px;
      box-shadow:0 2px 12px rgba(0,0,0,0.06); }
    .stat-icon { width:44px; height:44px; border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      font-size:1.2rem; margin-bottom:12px; }
    .stat-value { font-size:1.5rem; font-weight:700; color:#1a1f36; }
    .stat-label { font-size:0.78rem; color:#8492a6; font-weight:500; }
    .form-control, .form-select { border-radius:10px; border:1.5px solid #e2e8f0;
      font-size:0.85rem; padding: 8px 12px; }
    .form-control:focus { border-color:#4361ee;
      box-shadow:0 0 0 3px rgba(67,97,238,0.15); }
    .btn-filter { background: linear-gradient(135deg,#4361ee,#5563eb);
      color:#fff; border:none; border-radius:10px; padding:8px 18px;
      font-size:0.85rem; font-weight:600; }
    .nav-tabs .nav-link { font-size:0.85rem; font-weight:600; color:#8492a6;
      border:none; padding:10px 20px; border-radius:10px 10px 0 0; }
    .nav-tabs .nav-link.active { color:#4361ee; background:#f0f4ff;
      border-bottom: 2px solid #4361ee; }
    .chart-wrapper { position:relative; height:250px; }
    .page-body { padding: 24px 28px; }
    .badge-low { background:#ffeaec; color:#e63946;
      font-size:0.72rem; padding:4px 10px; border-radius:20px; }
    .badge-ok  { background:#e8faf0; color:#2dc653;
      font-size:0.72rem; padding:4px 10px; border-radius:20px; }

    @media print {
      .sidebar, .topbar, .filter-section,
      .nav-tabs, .no-print { display: none !important; }
      .main-content { margin-left: 0 !important; }
      .tab-pane { display: block !important; opacity: 1 !important; }
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
    <a href="reports.php" class="nav-link active"><i class="bi bi-bar-chart-line"></i> Reports</a>
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
      <h6>Reports</h6>
      <nav><ol class="breadcrumb">
        <li class="breadcrumb-item text-muted">Home</li>
        <li class="breadcrumb-item active">Reports</li>
      </ol></nav>
    </div>
    <div class="d-flex gap-2 no-print">
      <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"
              style="border-radius:8px; font-size:0.8rem;">
        <i class="bi bi-printer me-1"></i> Print Report
      </button>
      <span class="text-muted" style="font-size:0.8rem; line-height:2.2;">
        <?= date('D, d M Y') ?>
      </span>
    </div>
  </div>

  <div class="page-body">

    <!-- DATE FILTER -->
    <div class="content-card mb-4 filter-section">
      <form method="GET" action="" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label" style="font-size:0.82rem; font-weight:600;">From Date</label>
          <input type="date" name="from" class="form-control" value="<?= $from ?>"/>
        </div>
        <div class="col-md-3">
          <label class="form-label" style="font-size:0.82rem; font-weight:600;">To Date</label>
          <input type="date" name="to" class="form-control" value="<?= $to ?>"/>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn-filter btn w-100">
            <i class="bi bi-funnel me-1"></i> Filter
          </button>
        </div>
        <div class="col-md-2">
          <a href="reports.php" class="btn btn-outline-secondary w-100"
             style="border-radius:10px; font-size:0.85rem;">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
          </a>
        </div>
      </form>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#e8faf0; color:#2dc653;">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="stat-value">₹<?= number_format($totalSalesAmt, 2) ?></div>
          <div class="stat-label">Sales Revenue</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff4e5; color:#f77f00;">
            <i class="bi bi-cart4"></i>
          </div>
          <div class="stat-value">₹<?= number_format($totalPurchaseAmt, 2) ?></div>
          <div class="stat-label">Purchase Cost</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#eef0ff; color:#4361ee;">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <div class="stat-value">₹<?= number_format($totalSalesAmt - $totalPurchaseAmt, 2) ?></div>
          <div class="stat-label">Net Profit</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#ffeaec; color:#e63946;">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <div class="stat-value"><?= $lowStockCount ?></div>
          <div class="stat-label">Low Stock Items</div>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="content-card">
      <ul class="nav nav-tabs mb-4 no-print" id="reportTabs">
        <li class="nav-item">
          <a class="nav-link active" data-bs-toggle="tab" href="#salesTab">
            <i class="bi bi-receipt me-1"></i> Sales Report
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="tab" href="#purchaseTab">
            <i class="bi bi-cart me-1"></i> Purchase Report
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="tab" href="#stockTab">
            <i class="bi bi-box-seam me-1"></i> Stock Report
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="tab" href="#chartTab">
            <i class="bi bi-bar-chart me-1"></i> Charts
          </a>
        </li>
      </ul>

      <div class="tab-content">

        <!-- SALES TAB -->
        <div class="tab-pane fade show active" id="salesTab">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="card-title">🧾 Sales Report
              <span class="text-muted" style="font-size:0.8rem; font-weight:400;">
                (<?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?>)
              </span>
            </div>
            <div>
              <span class="badge bg-success bg-opacity-10 text-success"
                    style="font-size:0.78rem; padding:5px 12px; border-radius:20px;">
                Total: ₹<?= number_format($totalSalesAmt, 2) ?>
              </span>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Product</th>
                  <th>Customer</th>
                  <th>Quantity</th>
                  <th>Amount</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($salesReport) > 0): $i=1;
                  while ($s = mysqli_fetch_assoc($salesReport)): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><strong><?= htmlspecialchars($s['product_name']) ?></strong></td>
                  <td><?= htmlspecialchars($s['customer_name'] ?? 'Walk-in') ?></td>
                  <td><?= $s['quantity'] ?></td>
                  <td style="color:#2dc653; font-weight:600;">
                    ₹<?= number_format($s['total_price'], 2) ?>
                  </td>
                  <td><?= date('d M Y', strtotime($s['sale_date'])) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    No sales found for selected period
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
              <tfoot>
                <tr style="background:#f8f9ff;">
                  <td colspan="3"><strong>Total</strong></td>
                  <td><strong><?= $totalSalesQty ?> units</strong></td>
                  <td style="color:#2dc653; font-weight:700;">
                    ₹<?= number_format($totalSalesAmt, 2) ?>
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- PURCHASE TAB -->
        <div class="tab-pane fade" id="purchaseTab">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="card-title">🛒 Purchase Report
              <span class="text-muted" style="font-size:0.8rem; font-weight:400;">
                (<?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?>)
              </span>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning"
                  style="font-size:0.78rem; padding:5px 12px; border-radius:20px;">
              Total: ₹<?= number_format($totalPurchaseAmt, 2) ?>
            </span>
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
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($purchaseReport) > 0): $i=1;
                  while ($p = mysqli_fetch_assoc($purchaseReport)): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><strong><?= htmlspecialchars($p['product_name']) ?></strong></td>
                  <td><?= htmlspecialchars($p['supplier_name'] ?? 'N/A') ?></td>
                  <td><?= $p['quantity'] ?></td>
                  <td style="color:#f77f00; font-weight:600;">
                    ₹<?= number_format($p['total_cost'], 2) ?>
                  </td>
                  <td><?= date('d M Y', strtotime($p['purchase_date'])) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    No purchases found for selected period
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
              <tfoot>
                <tr style="background:#f8f9ff;">
                  <td colspan="4"><strong>Total Cost</strong></td>
                  <td style="color:#f77f00; font-weight:700;">
                    ₹<?= number_format($totalPurchaseAmt, 2) ?>
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- STOCK TAB -->
        <div class="tab-pane fade" id="stockTab">
          <div class="card-title mb-3">📦 Current Stock Report</div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Current Stock</th>
                  <th>Reorder Level</th>
                  <th>Unit Price</th>
                  <th>Stock Value</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php $i=1; $totalValue=0;
                  while ($p = mysqli_fetch_assoc($stockReport)):
                    $value = $p['quantity'] * $p['price'];
                    $totalValue += $value;
                ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                  <td><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></td>
                  <td><?= $p['quantity'] ?></td>
                  <td><?= $p['reorder_level'] ?></td>
                  <td>₹<?= number_format($p['price'], 2) ?></td>
                  <td><strong>₹<?= number_format($value, 2) ?></strong></td>
                  <td>
                    <?php if ($p['quantity'] <= $p['reorder_level']): ?>
                      <span class="badge-low">Low Stock</span>
                    <?php else: ?>
                      <span class="badge-ok">In Stock</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
              <tfoot>
                <tr style="background:#f8f9ff;">
                  <td colspan="6"><strong>Total Stock Value</strong></td>
                  <td style="font-weight:700; color:#4361ee;">
                    ₹<?= number_format($totalValue, 2) ?>
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- CHART TAB -->
        <div class="tab-pane fade" id="chartTab">
          <div class="card-title mb-3">📊 Sales vs Purchases (Last 6 Months)</div>
          <div class="chart-wrapper">
            <canvas id="reportChart"></canvas>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php
// Chart data - last 6 months
$salesData     = [];
$purchaseData  = [];
$chartLabels   = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $s = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(total_price) as total FROM sales
         WHERE DATE_FORMAT(sale_date,'%Y-%m')='$month'"));
    $p = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(total_cost) as total FROM purchases
         WHERE DATE_FORMAT(purchase_date,'%Y-%m')='$month'"));
    $salesData[]    = $s['total'] ?? 0;
    $purchaseData[] = $p['total'] ?? 0;
    $chartLabels[]  = $label;
}
?>
const ctx = document.getElementById('reportChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [
      {
        label: 'Sales (₹)',
        data: <?= json_encode($salesData) ?>,
        backgroundColor: 'rgba(45,198,83,0.7)',
        borderRadius: 8
      },
      {
        label: 'Purchases (₹)',
        data: <?= json_encode($purchaseData) ?>,
        backgroundColor: 'rgba(247,127,0,0.7)',
        borderRadius: 8
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top' },
      tooltip: {
        callbacks: {
          label: ctx => ' ₹' + Number(ctx.raw).toLocaleString('en-IN')
        }
      }
    },
    scales: {
      x: { grid: { display: false } },
      y: {
        grid: { color: '#f0f2f5' },
        ticks: { callback: v => '₹' + v.toLocaleString('en-IN') }
      }
    }
  }
});
</script>
</body>
</html>
