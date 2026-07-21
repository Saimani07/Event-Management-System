<?php
$adminPage = basename($_SERVER['PHP_SELF']);
$navItems = [
    'dashboard.php'         => ['icon' => 'fa-gauge', 'label' => 'Dashboard'],
    'manage-events.php'     => ['icon' => 'fa-calendar-days', 'label' => 'Events'],
    'manage-categories.php' => ['icon' => 'fa-tags', 'label' => 'Categories'],
    'manage-bookings.php'   => ['icon' => 'fa-ticket', 'label' => 'Bookings'],
    'manage-users.php'      => ['icon' => 'fa-users', 'label' => 'Users'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? clean($pageTitle) . ' - Admin' : 'Admin'; ?> - EventPro</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.dataTables.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-body">
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="admin-logo"><i class="fa-solid fa-ticket"></i> EventPro <span>Admin</span></div>
    <nav>
      <?php foreach ($navItems as $file => $item): ?>
        <a href="<?php echo BASE_URL; ?>/admin/<?php echo $file; ?>" class="<?php echo $adminPage === $file ? 'active' : ''; ?>">
          <i class="fa-solid <?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <a href="<?php echo BASE_URL; ?>/admin/logout.php" class="admin-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </aside>
  <main class="admin-main">
    <header class="admin-topbar">
      <button id="adminNavToggle" class="nav-toggle" style="display:none;"><i class="fa-solid fa-bars"></i></button>
      <div><?php echo isset($pageTitle) ? clean($pageTitle) : ''; ?></div>
      <div class="admin-user"><i class="fa-solid fa-circle-user"></i> <?php echo clean($_SESSION['admin_name'] ?? 'Admin'); ?></div>
    </header>
    <div class="admin-content">
      <script>window.APP_FLASHES = <?php echo json_encode(get_flashes()); ?>; window.BASE_URL = "<?php echo BASE_URL; ?>";</script>
