<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    log_activity('user', current_user_id(), 'Logged out');
}

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => BASE_URL . '/']);
}

$_SESSION = [];
session_destroy();
session_start();
set_flash('info', 'You have been logged out.');
redirect('/login.php');
