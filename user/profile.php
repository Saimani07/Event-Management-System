<?php
require_once __DIR__ . '/../config/config.php';
require_login();
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([current_user_id()]);
$user = $stmt->fetch();

$statsStmt = $pdo->prepare("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS active,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM bookings WHERE user_id = ?");
$statsStmt->execute([current_user_id()]);
$stats = $statsStmt->fetch();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = clean($_POST['full_name'] ?? '');
        $phone = clean($_POST['phone'] ?? '');

        if (strlen($fullName) < 3) $errors[] = 'Please enter a valid full name.';
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors[] = 'Please enter a valid phone number.';

        $photoFilename = $user['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploaded = handle_image_upload('profile_photo', __DIR__ . '/../uploads/profiles/');
            if ($uploaded === false) {
                $errors[] = 'Profile photo must be JPG, PNG, or WEBP under 5MB.';
            } elseif ($uploaded !== null) {
                $photoFilename = $uploaded;
            }
        }

        if (empty($errors)) {
            $update = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, profile_photo = ? WHERE id = ?");
            $update->execute([$fullName, $phone, $photoFilename, current_user_id()]);
            $_SESSION['user_name'] = $fullName;
            set_flash('success', 'Profile updated successfully.');
            redirect('/user/profile.php');
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (!is_strong_password($new)) {
            $errors[] = 'New password must be at least 8 characters with a letter and a number.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([password_hash($new, PASSWORD_BCRYPT), current_user_id()]);
            log_activity('user', current_user_id(), 'Changed password');
            set_flash('success', 'Password changed successfully.');
            redirect('/user/profile.php');
        }
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:800px;">
    <?php if (!empty($errors)): ?>
      <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#B91C1C; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.88rem;">
        <?php foreach ($errors as $e) echo '<div>' . clean($e) . '</div>'; ?>
      </div>
    <?php endif; ?>

    <div class="stats-grid" style="margin-bottom:32px;">
      <div class="card stat-card"><div class="num" data-counter="<?php echo (int) $stats['total']; ?>">0</div><div class="label">Total Bookings</div></div>
      <div class="card stat-card"><div class="num" data-counter="<?php echo (int) $stats['active']; ?>">0</div><div class="label">Active</div></div>
      <div class="card stat-card"><div class="num" data-counter="<?php echo (int) $stats['cancelled']; ?>">0</div><div class="label">Cancelled</div></div>
    </div>

    <div class="card" style="padding:32px; margin-bottom:24px;">
      <h3 style="margin-bottom:20px;">Edit Profile</h3>
      <form method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_profile">
        <div style="display:flex; align-items:center; gap:20px; margin-bottom:20px;">
          <img src="<?php echo $user['profile_photo'] ? BASE_URL . '/uploads/profiles/' . clean($user['profile_photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']); ?>"
               style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid var(--border);">
          <input type="file" name="profile_photo" accept="image/*" class="form-control">
        </div>
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?php echo clean($user['full_name']); ?>" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" class="form-control" value="<?php echo clean($user['email']); ?>" disabled>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?php echo clean($user['phone']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>

    <div class="card" style="padding:32px;">
      <h3 style="margin-bottom:20px;">Change Password</h3>
      <form method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
          <label>Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Password</button>
      </form>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
