<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? clean($pageTitle) . ' - ' . APP_NAME : APP_NAME; ?></title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar">
  <div class="container">
    <a href="<?php echo BASE_URL; ?>/index.php" class="logo"><i class="fa-solid fa-ticket"></i> EventPro</a>
    <button class="nav-toggle" id="navToggle"><i class="fa-solid fa-bars"></i></button>
    <ul class="nav-links" id="navLinks">
      <li><a href="<?php echo BASE_URL; ?>/index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a></li>
      <li><a href="<?php echo BASE_URL; ?>/events.php" class="<?php echo $currentPage === 'events.php' ? 'active' : ''; ?>">Events</a></li>
      <?php if (is_logged_in()): ?>
        <li><a href="<?php echo BASE_URL; ?>/user/my-bookings.php">My Bookings</a></li>
        <li><a href="<?php echo BASE_URL; ?>/user/profile.php">Profile</a></li>
        <li><a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
      <?php else: ?>
        <li><a href="<?php echo BASE_URL; ?>/login.php">Login</a></li>
        <li><a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-sm">Sign Up</a></li>
      <?php endif; ?>
      <li><button id="darkModeToggle" class="btn btn-outline btn-sm" title="Toggle dark mode"><i class="fa-solid fa-moon"></i></button></li>
    </ul>
  </div>
</nav>

<div class="flash-stack" id="flashStack"></div>

<script>
  window.APP_FLASHES = <?php echo json_encode(get_flashes()); ?>;
  window.BASE_URL = "<?php echo BASE_URL; ?>";
</script>
