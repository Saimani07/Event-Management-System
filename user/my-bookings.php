<?php
require_once __DIR__ . '/../config/config.php';
require_login();
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT b.*, e.title, e.event_date, e.event_time, e.venue, e.image, e.slug
    FROM bookings b JOIN events e ON e.id = b.event_id
    WHERE b.user_id = ? ORDER BY b.booked_at DESC");
$stmt->execute([current_user_id()]);
$bookings = $stmt->fetchAll();

function booking_display_status($b) {
    if ($b['status'] === 'cancelled') return 'cancelled';
    if (strtotime($b['event_date']) < strtotime('today')) return 'completed';
    return 'upcoming';
}

$pageTitle = 'My Bookings';
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-head"><div><h2>My Bookings</h2><p>Track and manage your event reservations</p></div></div>

    <div style="display:flex; gap:10px; margin-bottom:24px;" id="bookingTabs">
      <button class="btn btn-primary btn-sm tab-btn" data-tab="all">All</button>
      <button class="btn btn-outline btn-sm tab-btn" data-tab="upcoming">Upcoming</button>
      <button class="btn btn-outline btn-sm tab-btn" data-tab="completed">Completed</button>
      <button class="btn btn-outline btn-sm tab-btn" data-tab="cancelled">Cancelled</button>
    </div>

    <?php if (empty($bookings)): ?>
      <div class="empty-state"><i class="fa-regular fa-ticket"></i><p>You haven't booked any events yet.</p><a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-primary btn-sm" style="margin-top:12px;">Browse Events</a></div>
    <?php else: ?>
      <div class="grid" id="bookingGrid">
        <?php foreach ($bookings as $b): $status = booking_display_status($b); ?>
          <div class="card" data-status="<?php echo $status; ?>" style="padding:20px;">
            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:12px;">
              <span style="font-size:0.75rem; font-weight:700; padding:4px 10px; border-radius:999px;
                background:<?php echo $status === 'upcoming' ? '#DCFCE7' : ($status === 'cancelled' ? '#FEE2E2' : '#E0E7FF'); ?>;
                color:<?php echo $status === 'upcoming' ? '#166534' : ($status === 'cancelled' ? '#991B1B' : '#3730A3'); ?>;">
                <?php echo ucfirst($status); ?>
              </span>
              <span style="font-size:0.75rem; color:var(--muted);"><?php echo clean($b['booking_code']); ?></span>
            </div>
            <h3 style="margin-bottom:8px;"><?php echo clean($b['title']); ?></h3>
            <div class="meta" style="display:flex; flex-direction:column; gap:6px; color:var(--muted); font-size:0.88rem; margin-bottom:16px;">
              <span><i class="fa-regular fa-calendar"></i> <?php echo format_date($b['event_date']); ?> · <?php echo format_time($b['event_time']); ?></span>
              <span><i class="fa-solid fa-location-dot"></i> <?php echo clean($b['venue']); ?></span>
              <span><i class="fa-solid fa-chair"></i> <?php echo (int) $b['seats_booked']; ?> seat(s) · <?php echo format_currency($b['total_amount']); ?></span>
            </div>
            <div style="display:flex; gap:8px;">
              <a href="<?php echo BASE_URL; ?>/event-details.php?slug=<?php echo urlencode($b['slug']); ?>" class="btn btn-outline btn-sm" style="flex:1;">View Event</a>
              <?php if ($status === 'upcoming'): ?>
                <form method="POST" action="<?php echo BASE_URL; ?>/user/cancel-booking.php" style="flex:1;" onsubmit="return confirm('Cancel this booking?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                  <button type="submit" class="btn btn-danger btn-sm btn-block">Cancel</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.className = 'btn btn-outline btn-sm tab-btn');
    btn.className = 'btn btn-primary btn-sm tab-btn';
    const tab = btn.dataset.tab;
    document.querySelectorAll('#bookingGrid > div').forEach(card => {
      card.style.display = (tab === 'all' || card.dataset.status === tab) ? '' : 'none';
    });
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
