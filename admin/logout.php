<?php
require_once __DIR__ . '/../config/config.php';
if (is_admin_logged_in()) {
    log_activity('admin', $_SESSION['admin_id'], 'Logged out');
}
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
redirect('/admin/login.php');
