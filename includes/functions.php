<?php
/**
 * Shared helper / utility functions
 */

function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

function require_admin() {
    if (!is_admin_logged_in()) {
        redirect('/admin/login.php');
    }
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function set_flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes() {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function generate_booking_code() {
    return 'EVT-' . date('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
}

function generate_slug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    return $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
}

/**
 * Validate password strength: min 8 chars, at least one letter and one number
 */
function is_strong_password($password) {
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

/**
 * Handle a secure image upload. Returns the stored filename on success, or false on failure.
 */
function handle_image_upload($fileInput, $destinationDir = UPLOAD_DIR) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // no file provided, not necessarily an error
    }
    $file = $_FILES[$fileInput];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        return false;
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => false,
    };
    if (!$ext) return false;

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }

    $filename = 'evt_' . bin2hex(random_bytes(12)) . '.' . $ext;
    $target = rtrim($destinationDir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return false;
    }

    return $filename;
}

function log_activity($actorType, $actorId, $action, $details = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('INSERT INTO activity_logs (actor_type, actor_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$actorType, $actorId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

function format_currency($amount) {
    return '₹' . number_format((float) $amount, 2);
}

function format_date($date) {
    return date('d M Y', strtotime($date));
}

function format_time($time) {
    return date('h:i A', strtotime($time));
}
