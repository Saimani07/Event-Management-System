<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config/config.php';
}
http_response_code(404);
$pageTitle = 'Page Not Found';
include __DIR__ . '/includes/header.php';
?>
<div style="min-height:60vh; display:flex; align-items:center; justify-content:center; text-align:center; padding:40px 20px;">
  <div>
    <div style="font-size:5rem; font-weight:800; background:linear-gradient(135deg,var(--primary),var(--accent)); -webkit-background-clip:text; background-clip:text; color:transparent;">404</div>
    <h2 style="margin:16px 0 8px;">Page not found</h2>
    <p style="color:var(--muted); margin-bottom:24px;">The page you're looking for doesn't exist or may have been moved.</p>
    <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary">Back to Home</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
