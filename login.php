<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) redirect('/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = 'Please enter a valid email and password.';
    }

    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT id, full_name, password, status FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] === 'suspended') {
            $errors[] = 'Your account has been suspended. Please contact support.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
                $stmt->execute([hash('sha256', $token), $user['id']]);
                setcookie('remember_token', $user['id'] . ':' . $token, [
                    'expires'  => time() + (30 * 24 * 3600),
                    'path'     => BASE_URL . '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            log_activity('user', $user['id'], 'Logged in');
            set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect('/index.php');
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card card glass">
    <h2>Welcome back</h2>
    <p class="sub">Log in to manage your bookings.</p>

    <?php if (!empty($errors)): ?>
      <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#B91C1C; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.88rem;">
        <?php foreach ($errors as $e) echo '<div>' . clean($e) . '</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" value="<?php echo clean($_POST['email'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="form-group" style="display:flex; align-items:center; justify-content:space-between;">
        <label style="display:flex; align-items:center; gap:8px; font-weight:400;">
          <input type="checkbox" name="remember" style="width:auto;"> Remember me
        </label>
        <a href="<?php echo BASE_URL; ?>/forgot-password.php" style="font-size:0.85rem; color:var(--primary);">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
    <div class="auth-switch">Don't have an account? <a href="<?php echo BASE_URL; ?>/register.php">Sign up</a></div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
