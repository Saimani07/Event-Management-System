<?php
require_once __DIR__ . '/../config/config.php';
if (is_admin_logged_in()) redirect('/admin/dashboard.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        $errors[] = 'Invalid email or password.';
    } elseif ($admin['status'] === 'suspended') {
        $errors[] = 'This admin account has been suspended.';
    } else {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        log_activity('admin', $admin['id'], 'Logged in');
        redirect('/admin/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - EventPro</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card card glass">
    <h2><i class="fa-solid fa-shield-halved"></i> Admin Login</h2>
    <p class="sub">Restricted access — authorized personnel only.</p>
    <?php if (!empty($errors)): ?>
      <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#B91C1C; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.88rem;">
        <?php foreach ($errors as $e) echo '<div>' . clean($e) . '</div>'; ?>
      </div>
    <?php endif; ?>
    <form method="POST">
      <?php echo csrf_field(); ?>
      <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
    <div class="auth-switch"><a href="<?php echo BASE_URL; ?>/index.php">&larr; Back to site</a></div>
  </div>
</div>
</body>
</html>
