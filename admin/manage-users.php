<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle_status') {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        $new = $current === 'active' ? 'suspended' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new, $id]);
        log_activity('admin', $_SESSION['admin_id'], ($new === 'suspended' ? 'Suspended' : 'Activated') . ' user ID ' . $id);
        set_flash('success', 'User ' . ($new === 'suspended' ? 'suspended' : 'activated') . '.');
    }

    if ($action === 'delete_user') {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        log_activity('admin', $_SESSION['admin_id'], 'Deleted user ID ' . $id);
        set_flash('success', 'User deleted.');
    }

    redirect('/admin/manage-users.php');
}

$search = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($search !== '') { $where = "(full_name LIKE ? OR email LIKE ?)"; array_push($params, "%$search%", "%$search%"); }

$stmt = $pdo->prepare("SELECT u.*, COUNT(b.id) AS booking_count FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id WHERE $where GROUP BY u.id ORDER BY u.created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/includes/header.php';
?>

<form method="GET" style="display:flex; gap:8px; margin-bottom:20px;">
  <input type="text" name="q" class="form-control" placeholder="Search by name or email..." value="<?php echo clean($search); ?>" style="width:280px;">
  <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-search"></i></button>
</form>

<div class="card data-table-wrap">
  <table class="styled-table dt-table" style="width:100%;">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?php echo clean($u['full_name']); ?></td>
        <td><?php echo clean($u['email']); ?></td>
        <td><?php echo clean($u['phone']); ?></td>
        <td><?php echo (int) $u['booking_count']; ?></td>
        <td><span class="status-pill status-<?php echo $u['status']; ?>"><?php echo ucfirst($u['status']); ?></span></td>
        <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
        <td style="white-space:nowrap;">
          <form method="POST" style="display:inline;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
            <button type="submit" class="btn btn-outline btn-sm"><?php echo $u['status'] === 'active' ? 'Suspend' : 'Activate'; ?></button>
          </form>
          <form method="POST" style="display:inline;" onsubmit="return confirmAction(this, 'Delete this user?', 'This will remove their account permanently.');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
