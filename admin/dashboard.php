<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
$pdo = getDBConnection();

$stats = [
    'events'    => (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
    'users'     => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'bookings'  => (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'cancelled'")->fetchColumn(),
    'today'     => (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(booked_at) = CURDATE()")->fetchColumn(),
    'upcoming'  => (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'")->fetchColumn(),
    'revenue'   => (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status != 'cancelled'")->fetchColumn(),
];

// Monthly bookings (last 6 months)
$monthlyRaw = $pdo->query("SELECT DATE_FORMAT(booked_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM bookings WHERE booked_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym")->fetchAll();
$monthlyLabels = []; $monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $monthlyLabels[] = date('M Y', strtotime("-$i months"));
    $found = array_filter($monthlyRaw, fn($r) => $r['ym'] === $ym);
    $monthlyData[] = $found ? (int) array_values($found)[0]['cnt'] : 0;
}

// Category-wise events
$catRaw = $pdo->query("SELECT c.name, c.color, COUNT(e.id) AS cnt FROM categories c
    LEFT JOIN events e ON e.category_id = c.id GROUP BY c.id")->fetchAll();

// User growth (last 6 months)
$userRaw = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym")->fetchAll();
$userLabels = []; $userData = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $userLabels[] = date('M', strtotime("-$i months"));
    $found = array_filter($userRaw, fn($r) => $r['ym'] === $ym);
    $userData[] = $found ? (int) array_values($found)[0]['cnt'] : 0;
}

$recentBookings = $pdo->query("SELECT b.booking_code, b.attendee_name, b.total_amount, b.status, b.booked_at, e.title
    FROM bookings b JOIN events e ON e.id = b.event_id ORDER BY b.booked_at DESC LIMIT 6")->fetchAll();

$recentUsers = $pdo->query("SELECT full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="dash-grid">
  <div class="card dash-card"><div class="icon-box" style="background:var(--primary);"><i class="fa-solid fa-calendar-days"></i></div><div><div class="num"><?php echo $stats['events']; ?></div><div class="label">Total Events</div></div></div>
  <div class="card dash-card"><div class="icon-box" style="background:var(--secondary);"><i class="fa-solid fa-users"></i></div><div><div class="num"><?php echo $stats['users']; ?></div><div class="label">Total Users</div></div></div>
  <div class="card dash-card"><div class="icon-box" style="background:var(--accent);"><i class="fa-solid fa-ticket"></i></div><div><div class="num"><?php echo $stats['bookings']; ?></div><div class="label">Total Bookings</div></div></div>
  <div class="card dash-card"><div class="icon-box" style="background:var(--success);"><i class="fa-solid fa-calendar-check"></i></div><div><div class="num"><?php echo $stats['today']; ?></div><div class="label">Today's Bookings</div></div></div>
  <div class="card dash-card"><div class="icon-box" style="background:var(--warning);"><i class="fa-solid fa-clock"></i></div><div><div class="num"><?php echo $stats['upcoming']; ?></div><div class="label">Upcoming Events</div></div></div>
  <div class="card dash-card"><div class="icon-box" style="background:var(--danger);"><i class="fa-solid fa-indian-rupee-sign"></i></div><div><div class="num"><?php echo format_currency($stats['revenue']); ?></div><div class="label">Total Revenue</div></div></div>
</div>

<div class="chart-grid">
  <div class="card chart-card">
    <h3>Monthly Bookings</h3>
    <canvas id="monthlyBookingsChart" height="100"></canvas>
  </div>
  <div class="card chart-card">
    <h3>Category-wise Events</h3>
    <canvas id="categoryChart" height="100"></canvas>
  </div>
</div>

<div class="chart-grid" style="grid-template-columns:1fr;">
  <div class="card chart-card">
    <h3>User Growth (Last 6 Months)</h3>
    <canvas id="userGrowthChart" height="70"></canvas>
  </div>
</div>

<div class="chart-grid" style="grid-template-columns:1.4fr 1fr;">
  <div class="card data-table-wrap">
    <h3 style="margin-bottom:16px;">Recent Bookings</h3>
    <table class="styled-table">
      <thead><tr><th>Code</th><th>Event</th><th>Attendee</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recentBookings as $b): ?>
        <tr>
          <td><?php echo clean($b['booking_code']); ?></td>
          <td><?php echo clean($b['title']); ?></td>
          <td><?php echo clean($b['attendee_name']); ?></td>
          <td><?php echo format_currency($b['total_amount']); ?></td>
          <td><span class="status-pill status-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card data-table-wrap">
    <h3 style="margin-bottom:16px;">Recent Registrations</h3>
    <?php foreach ($recentUsers as $u): ?>
      <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border);">
        <div>
          <div style="font-weight:600; font-size:0.9rem;"><?php echo clean($u['full_name']); ?></div>
          <div style="font-size:0.78rem; color:var(--muted);"><?php echo clean($u['email']); ?></div>
        </div>
        <div style="font-size:0.78rem; color:var(--muted);"><?php echo date('d M', strtotime($u['created_at'])); ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
new Chart(document.getElementById('monthlyBookingsChart'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($monthlyLabels); ?>,
    datasets: [{
      label: 'Bookings',
      data: <?php echo json_encode($monthlyData); ?>,
      borderColor: '#2563EB',
      backgroundColor: 'rgba(37,99,235,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 4,
      pointBackgroundColor: '#2563EB'
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('categoryChart'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode(array_column($catRaw, 'name')); ?>,
    datasets: [{
      data: <?php echo json_encode(array_column($catRaw, 'cnt')); ?>,
      backgroundColor: <?php echo json_encode(array_column($catRaw, 'color')); ?>
    }]
  },
  options: { plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('userGrowthChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($userLabels); ?>,
    datasets: [{
      label: 'New Users',
      data: <?php echo json_encode($userData); ?>,
      backgroundColor: '#4F46E5',
      borderRadius: 6
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
