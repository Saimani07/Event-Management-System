<?php
require_once __DIR__ . '/config/config.php';
$pdo = getDBConnection();

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT e.*, c.name AS category_name, c.color AS category_color
    FROM events e JOIN categories c ON c.id = e.category_id WHERE e.slug = ?");
$stmt->execute([$slug]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$pageTitle = $event['title'];
$gallery = json_decode($event['gallery'] ?? '[]', true) ?: [];
$schedule = json_decode($event['schedule'] ?? '[]', true) ?: [];

$related = $pdo->prepare("SELECT * FROM events WHERE category_id = ? AND id != ? AND status = 'upcoming' LIMIT 3");
$related->execute([$event['category_id'], $event['id']]);
$relatedEvents = $related->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="background:linear-gradient(160deg,#0F172A,#1E293B); padding:60px 0;">
  <div class="container" style="color:#fff;">
    <span class="badge" style="position:static; display:inline-block; margin-bottom:14px;"><?php echo clean($event['category_name']); ?></span>
    <h1 style="font-size:2.2rem; font-weight:800; max-width:760px;"><?php echo clean($event['title']); ?></h1>
    <p style="color:rgba(255,255,255,0.75); margin-top:10px; max-width:640px;"><?php echo clean($event['short_description'] ?: ''); ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="display:grid; grid-template-columns: 2fr 1fr; gap:36px; align-items:start;">
    <div>
      <div class="card" style="overflow:hidden; margin-bottom:24px;">
        <img src="<?php echo $event['image'] ? UPLOAD_URL . clean($event['image']) : 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1000&q=80'; ?>" alt="" style="width:100%; aspect-ratio:16/8; object-fit:cover;">
      </div>

      <?php if (!empty($gallery)): ?>
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); margin-bottom:24px;">
          <?php foreach ($gallery as $img): ?>
            <div class="card" style="overflow:hidden;"><img src="<?php echo UPLOAD_URL . clean($img); ?>" style="aspect-ratio:1; object-fit:cover;"></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="card" style="padding:28px; margin-bottom:24px;">
        <h3 style="margin-bottom:14px;">About this event</h3>
        <p style="color:var(--muted); white-space:pre-line;"><?php echo clean($event['description']); ?></p>
      </div>

      <?php if (!empty($schedule)): ?>
        <div class="card" style="padding:28px; margin-bottom:24px;">
          <h3 style="margin-bottom:18px;">Schedule</h3>
          <?php foreach ($schedule as $item): ?>
            <div style="display:flex; gap:16px; padding:12px 0; border-bottom:1px solid var(--border);">
              <div style="font-weight:700; color:var(--primary); min-width:90px;"><?php echo clean($item['time'] ?? ''); ?></div>
              <div><?php echo clean($item['title'] ?? ''); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($event['rules'])): ?>
        <div class="card" style="padding:28px; margin-bottom:24px;">
          <h3 style="margin-bottom:14px;">Rules & Guidelines</h3>
          <p style="color:var(--muted); white-space:pre-line;"><?php echo clean($event['rules']); ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($event['map_embed_url'])): ?>
        <div class="card" style="overflow:hidden;">
          <iframe src="<?php echo htmlspecialchars($event['map_embed_url'], ENT_QUOTES); ?>" width="100%" height="300" style="border:0;" loading="lazy"></iframe>
        </div>
      <?php endif; ?>
    </div>

    <div class="card glass" style="padding:28px; position:sticky; top:96px;">
      <div style="font-size:1.8rem; font-weight:800; color:var(--primary); margin-bottom:6px;">
        <?php echo $event['price'] == 0 ? 'FREE' : format_currency($event['price']); ?>
      </div>
      <div style="color:var(--muted); font-size:0.9rem; margin-bottom:20px;"><?php echo (int) $event['available_seats']; ?> of <?php echo (int) $event['total_seats']; ?> seats available</div>

      <ul style="display:flex; flex-direction:column; gap:14px; margin-bottom:24px;">
        <li style="display:flex; gap:10px; color:var(--muted);"><i class="fa-regular fa-calendar" style="color:var(--primary); width:18px;"></i> <?php echo format_date($event['event_date']); ?></li>
        <li style="display:flex; gap:10px; color:var(--muted);"><i class="fa-regular fa-clock" style="color:var(--primary); width:18px;"></i> <?php echo format_time($event['event_time']); ?></li>
        <li style="display:flex; gap:10px; color:var(--muted);"><i class="fa-solid fa-location-dot" style="color:var(--primary); width:18px;"></i> <?php echo clean($event['venue']); ?></li>
        <li style="display:flex; gap:10px; color:var(--muted);"><i class="fa-solid fa-user-tie" style="color:var(--primary); width:18px;"></i> <?php echo clean($event['organizer']); ?></li>
      </ul>

      <?php if ($event['available_seats'] <= 0): ?>
        <button class="btn btn-outline btn-block" disabled>Sold Out</button>
      <?php elseif (is_logged_in()): ?>
        <a href="<?php echo BASE_URL; ?>/user/book-event.php?slug=<?php echo urlencode($event['slug']); ?>" class="btn btn-primary btn-block">Book Now</a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary btn-block">Log In to Book</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if (!empty($relatedEvents)): ?>
<section class="section" style="background:var(--card); border-top:1px solid var(--border);">
  <div class="container">
    <div class="section-head"><div><h2>Related Events</h2></div></div>
    <div class="grid">
      <?php foreach ($relatedEvents as $ev): ?>
        <div class="card card-hover event-card">
          <div class="thumb">
            <img src="<?php echo $ev['image'] ? UPLOAD_URL . clean($ev['image']) : 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&q=80'; ?>" alt="">
            <span class="price-badge <?php echo $ev['price'] == 0 ? 'free' : ''; ?>"><?php echo $ev['price'] == 0 ? 'FREE' : format_currency($ev['price']); ?></span>
          </div>
          <div class="body">
            <h3><?php echo clean($ev['title']); ?></h3>
            <div class="meta"><span><i class="fa-regular fa-calendar"></i> <?php echo format_date($ev['event_date']); ?></span></div>
            <div class="footer">
              <span class="seats"><?php echo (int) $ev['available_seats']; ?> seats left</span>
              <a href="<?php echo BASE_URL; ?>/event-details.php?slug=<?php echo urlencode($ev['slug']); ?>" class="btn btn-primary btn-sm">View</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
