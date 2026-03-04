<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch Summary
$totalProducts   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products"))['cnt'];
$totalCategories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM categories"))['cnt'];
$today = date('Y-m-d');
$todaySales     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as total FROM sales WHERE sale_date='$today'"))['total'] ?? 0;
$todayPurchases = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_cost) as total FROM purchases WHERE purchase_date='$today'"))['total'] ?? 0;
$lowStockCount  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products WHERE quantity <= reorder_level"))['cnt'];
$lowStockResult = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 5");
$recentSales    = mysqli_query($conn, "SELECT s.*, p.name as product_name FROM sales s JOIN products p ON s.product_id=p.id ORDER BY s.sale_date DESC LIMIT 5");
// Fetch Summary
$totalProducts   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products"))['cnt'];
$totalCategories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM categories"))['cnt'];
$today = date('Y-m-d');
$todaySales     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as total FROM sales WHERE sale_date='$today'"))['total'] ?? 0;
$todayPurchases = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_cost) as total FROM purchases WHERE purchase_date='$today'"))['total'] ?? 0;
$lowStockCount  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products WHERE quantity <= reorder_level"))['cnt'];
$lowStockResult = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 5");
$recentSales    = mysqli_query($conn, "SELECT s.*, p.name as product_name FROM sales s JOIN products p ON s.product_id=p.id ORDER BY s.sale_date DESC LIMIT 5");

// Monthly Sales Chart Data
$monthlySales = []; $monthlyLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $res   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as total FROM sales WHERE DATE_FORMAT(sale_date,'%Y-%m')='$month'"));
    $monthlySales[] = $res['total'] ?? 0;
    $monthlyLabels[] = $label;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inventory - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    .user-avatar { width:36px; height:36px;
      background: linear-gradient(135deg,#7b93ff,#5563eb); border-radius:50%;
      display:flex; align-items:center; justify-content:center; color:#fff; font-weight:600; }

    /* STAT CARDS */
    .stat-card { background:#fff; border-radius:16px; padding:22px;
      box-shadow:0 2px 12px rgba(0,0,0,0.06); transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-icon { width:48px; height:48px; border-radius:12px;
      display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:14px; }
    .stat-icon.blue   { background:#eef0ff; color:#4361ee; }
    .stat-icon.green  { background:#e8faf0; color:#2dc653; }
    .stat-icon.orange { background:#fff4e5; color:#f77f00; }
    .stat-icon.red    { background:#ffeaec; color:#e63946; }
    .stat-value { font-size:1.8rem; font-weight:700; color:#1a1f36; }
    .stat-label { font-size:0.8rem; color:#8492a6; font-weight:500; }

    /* CONTENT CARD */
    .content-card { background:#fff; border-radius:16px; padding:22px;
      box-shadow:0 2px 12px rgba(0,0,0,0.06); }
    .content-card .card-title { font-weight:700; font-size:0.95rem; color:#1a1f36; }
    .table th { font-size:0.75rem; font-weight:600; color:#8492a6;
      text-transform:uppercase; letter-spacing:0.6px; }
    .table td { font-size:0.85rem; color:#3d4557; vertical-align:middle; }
    .chart-wrapper { position:relative; height:240px; }

    /* LOW STOCK */
    .stock-item { display:flex; align-items:center; justify-content:space-between;
      padding:10px 0; border-bottom:1px solid #f5f6fa; }
    .stock-item:last-child { border-bottom:none; }
    .stock-bar { height:6px; border-radius:3px; background:#f0f2f5; flex:1; margin:0 12px; }
    .stock-bar .fill { height:100%; border-radius:3px;
      background:linear-gradient(90deg,#e63946,#f77f00); }
    .page-body { padding:24px 28px; }
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
    <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
    <a href="products.php"  class="nav-link"><i class="bi bi-box-seam"></i> Products</a>
    <a href="categories.php" class="nav-link"><i class="bi bi-tags"></i> Categories</a>
    <a href="suppliers.php"  class="nav-link"><i class="bi bi-truck"></i> Suppliers</a>
  </nav>
  <div class="nav-label">Transactions</div>
  <nav class="nav flex-column">
    <a href="purchases.php"   class="nav-link"><i class="bi bi-cart-plus"></i> Purchases</a>
    <a href="sales.php"       class="nav-link"><i class="bi bi-receipt"></i> Sales</a>
    <a href="stock_alerts.php" class="nav-link">
      <i class="bi bi-exclamation-triangle"></i> Stock Alerts
      <?php if($lowStockCount > 0): ?>
        <span class="badge bg-danger ms-auto"><?= $lowStockCount ?></span>
      <?php endif; ?>
    </a>
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
      <h6>Dashboard</h6>
      <nav><ol class="breadcrumb"><li class="breadcrumb-item text-muted">Home</li>
        <li class="breadcrumb-item active">Dashboard</li></ol></nav>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="text-muted" style="font-size:0.8rem;"><i class="bi bi-calendar3"></i> <?= date('D, d M Y') ?></span>
      <div class="user-avatar">AD</div>
    </div>
  </div>

  <div class="page-body">

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-xl-3 col-md-6">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="bi bi-box-seam-fill"></i></div>
          <div class="stat-value"><?= $totalProducts ?></div>
          <div class="stat-label">Total Products</div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
          <div class="stat-value">₹<?= number_format($todaySales, 2) ?></div>
          <div class="stat-label">Today's Sales</div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="stat-card">
          <div class="stat-icon orange"><i class="bi bi-cart4"></i></div>
          <div class="stat-value">₹<?= number_format($todayPurchases, 2) ?></div>
          <div class="stat-label">Today's Purchases</div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="stat-card">
          <div class="stat-icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <div class="stat-value"><?= $lowStockCount ?></div>
          <div class="stat-label">Low Stock Alerts</div>
        </div>
      </div>
    </div>

    <!-- CHART + LOW STOCK -->
    <div class="row g-3 mb-4">
      <div class="col-xl-8">
        <div class="content-card">
          <div class="card-title mb-3">📈 Monthly Sales Overview</div>
          <div class="chart-wrapper">
            <canvas id="salesChart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="content-card">
          <div class="card-title mb-3">⚠️ Low Stock Items</div>
          <?php if (mysqli_num_rows($lowStockResult) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($lowStockResult)):
              $pct = $item['reorder_level'] > 0
                ? min(100, round(($item['quantity'] / $item['reorder_level']) * 100)) : 0;
            ?>
            <div class="stock-item">
              <div style="min-width:90px;">
                <div style="font-size:0.82rem;font-weight:600;"><?= htmlspecialchars($item['name']) ?></div>
                <div style="font-size:0.7rem;color:#8492a6;">Min: <?= $item['reorder_level'] ?></div>
              </div>
              <div class="stock-bar"><div class="fill" style="width:<?= $pct ?>%;"></div></div>
              <span class="badge bg-danger bg-opacity-10 text-danger"><?= $item['quantity'] ?></span>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-check-circle text-success fs-2"></i><br>All stocks healthy!
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RECENT SALES TABLE -->
    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="card-title">📋 Recent Sales</div>
        <a href="sales.php" style="font-size:0.8rem;color:#4361ee;">View All →</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th><th>Product</th><th>Customer</th>
              <th>Qty</th><th>Amount</th><th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($recentSales) > 0): $i=1;
              while ($s = mysqli_fetch_assoc($recentSales)): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($s['product_name']) ?></strong></td>
                <td><?= htmlspecialchars($s['customer_name'] ?? 'Walk-in') ?></td>
                <td><?= $s['quantity'] ?></td>
                <td style="color:#2dc653;font-weight:600;">₹<?= number_format($s['total_price'],2) ?></td>
                <td><?= date('d M Y', strtotime($s['sale_date'])) ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="6" class="text-center text-muted py-3">No sales yet today</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- end page-body -->
</div><!-- end main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const grad = ctx.createLinearGradient(0,0,0,240);
grad.addColorStop(0, 'rgba(67,97,238,0.35)');
grad.addColorStop(1, 'rgba(67,97,238,0)');

new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($monthlyLabels) ?>,
    datasets: [{
      label: 'Sales (₹)',
      data: <?= json_encode($monthlySales) ?>,
      fill: true,
      backgroundColor: grad,
      borderColor: '#4361ee',
      borderWidth: 2.5,
      pointBackgroundColor: '#fff',
      pointBorderColor: '#4361ee',
      pointBorderWidth: 2,
      pointRadius: 5,
      tension: 0.4
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#8492a6', font: { size:11 } } },
      y: { grid: { color: '#f0f2f5' }, ticks: { color: '#8492a6', font: { size:11 },
        callback: v => '₹' + v.toLocaleString('en-IN') } }
    }
  }
});
</script>
</body>
</html>