<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel_booking') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $b = $stmt->fetch();
            if ($b && $b['status'] !== 'cancelled') {
                $pdo->prepare("UPDATE bookings SET status='cancelled', cancelled_at=NOW() WHERE id=?")->execute([$id]);
                $pdo->prepare("UPDATE events SET available_seats = available_seats + ? WHERE id=?")->execute([$b['seats_booked'], $b['event_id']]);
                log_activity('admin', $_SESSION['admin_id'], 'Cancelled booking ' . $b['booking_code']);
                set_flash('success', 'Booking cancelled.');
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Could not cancel booking.');
        }
        redirect('/admin/manage-bookings.php');
    }
}

// ---- CSV export ----
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $pdo->query("SELECT b.booking_code, e.title, b.attendee_name, b.attendee_email, b.attendee_phone,
        b.seats_booked, b.total_amount, b.status, b.booked_at FROM bookings b JOIN events e ON e.id=b.event_id
        ORDER BY b.booked_at DESC")->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bookings_export_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Booking Code','Event','Attendee','Email','Phone','Seats','Amount','Status','Booked At']);
    foreach ($rows as $r) fputcsv($out, $r);
    fclose($out);
    exit;
}

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$where = ['1=1']; $params = [];
if ($search !== '') { $where[] = "(b.booking_code LIKE ? OR b.attendee_name LIKE ?)"; array_push($params, "%$search%", "%$search%"); }
if (in_array($statusFilter, ['confirmed','completed','cancelled'])) { $where[] = "b.status = ?"; $params[] = $statusFilter; }
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT b.*, e.title FROM bookings b JOIN events e ON e.id = b.event_id
    WHERE $whereSql ORDER BY b.booked_at DESC");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Manage Bookings';
include __DIR__ . '/includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
  <form method="GET" style="display:flex; gap:8px;">
    <input type="text" name="q" class="form-control" placeholder="Search code or attendee..." value="<?php echo clean($search); ?>" style="width:220px;">
    <select name="status" class="form-control" onchange="this.form.submit()">
      <option value="">All Status</option>
      <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
      <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
      <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
    </select>
    <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-search"></i></button>
  </form>
  <a href="?export=csv" class="btn btn-primary"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
</div>

<div class="card data-table-wrap">
  <table class="styled-table dt-table" style="width:100%;">
    <thead><tr><th>Code</th><th>Event</th><th>Attendee</th><th>Seats</th><th>Amount</th><th>Status</th><th>Booked</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($bookings as $b): ?>
      <tr>
        <td><?php echo clean($b['booking_code']); ?></td>
        <td><?php echo clean($b['title']); ?></td>
        <td><?php echo clean($b['attendee_name']); ?><br><span style="color:var(--muted); font-size:0.78rem;"><?php echo clean($b['attendee_email']); ?></span></td>
        <td><?php echo (int) $b['seats_booked']; ?></td>
        <td><?php echo format_currency($b['total_amount']); ?></td>
        <td><span class="status-pill status-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
        <td><?php echo date('d M Y', strtotime($b['booked_at'])); ?></td>
        <td>
          <?php if ($b['status'] !== 'cancelled'): ?>
          <form method="POST" onsubmit="return confirmAction(this, 'Cancel this booking?');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="cancel_booking">
            <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
            <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
          </form>
          <?php else: ?>
            <span style="color:var(--muted); font-size:0.8rem;">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
