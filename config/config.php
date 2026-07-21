<?php
/**
 * App configuration & session bootstrap
 */

// Session hardening — must run before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
// Uncomment the next line when serving over HTTPS in production
// ini_set('session.cookie_secure', 1);

session_start();

// Session timeout (30 minutes of inactivity)
define('SESSION_TIMEOUT', 1800);
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// Regenerate session id periodically to prevent fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 900) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

define('APP_NAME', 'EventPro');
define('BASE_URL', ''); // change to your deployment path
define('UPLOAD_DIR', __DIR__ . '/../uploads/events/');
define('UPLOAD_URL', BASE_URL . '/uploads/events/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
