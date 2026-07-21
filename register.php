<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) redirect('/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fullName = clean($_POST['full_name'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone    = clean($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || strlen($fullName) < 3) {
        $errors[] = 'Please enter your full name (min 3 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!is_strong_password($password)) {
        $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists. Please log in instead.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)');
        $stmt->execute([$fullName, $email, $phone, $hash]);
        $userId = (int) $pdo->lastInsertId();

        log_activity('user', $userId, 'Registered a new account');

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $fullName;

        set_flash('success', 'Welcome to EventPro, ' . $fullName . '!');
        redirect('/index.php');
    }
}

$pageTitle = 'Create Account';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card card glass">
    <h2>Create your account</h2>
    <p class="sub">Join EventPro and start booking amazing events.</p>

    <?php if (!empty($errors)): ?>
      <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#B91C1C; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.88rem;">
        <?php foreach ($errors as $e) echo '<div>' . clean($e) . '</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?php echo clean($_POST['full_name'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" value="<?php echo clean($_POST['email'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" class="form-control" value="<?php echo clean($_POST['phone'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required minlength="8">
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="8">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>
    <div class="auth-switch">Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Log in</a></div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
