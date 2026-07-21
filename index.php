<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Home';

$pdo = getDBConnection();

$upcoming = $pdo->query("SELECT e.*, c.name AS category_name, c.color AS category_color
    FROM events e JOIN categories c ON c.id = e.category_id
    WHERE e.status = 'upcoming' AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC LIMIT 6")->fetchAll();

$categories = $pdo->query("SELECT c.*, COUNT(e.id) AS event_count
    FROM categories c LEFT JOIN events e ON e.category_id = c.id
    GROUP BY c.id ORDER BY event_count DESC")->fetchAll();

$stats = [
    'events'   => (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
    'users'    => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'bookings' => (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'cancelled'")->fetchColumn(),
];

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <h1>Discover & book unforgettable events near you</h1>
    <p>From concerts to conferences — find experiences worth showing up for, and reserve your spot in seconds.</p>
    <div class="hero-actions">
      <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-primary">Browse Events</a>
      <a href="#categories" class="btn btn-outline" style="color:#fff; border-color:rgba(255,255,255,0.3);">Explore Categories</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="stats-grid">
      <div class="card stat-card">
        <div class="num" data-counter="<?php echo $stats['events']; ?>">0</div>
        <div class="label">Events Hosted</div>
      </div>
      <div class="card stat-card">
        <div class="num" data-counter="<?php echo $stats['users']; ?>">0</div>
        <div class="label">Registered Members</div>
      </div>
      <div class="card stat-card">
        <div class="num" data-counter="<?php echo $stats['bookings']; ?>">0</div>
        <div class="label">Tickets Booked</div>
      </div>
      <div class="card stat-card">
        <div class="num" data-counter="98">0</div>
        <div class="label">% Satisfaction</div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0;">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Upcoming Events</h2>
        <p>Hand-picked events happening soon</p>
      </div>
      <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-outline btn-sm">View All <i class="fa-solid fa-arrow-right"></i></a>
    </div>

    <?php if (empty($upcoming)): ?>
      <div class="empty-state"><i class="fa-regular fa-calendar-xmark"></i><p>No upcoming events right now. Check back soon!</p></div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($upcoming as $ev): ?>
          <div class="card card-hover event-card">
            <div class="thumb">
              <img src="<?php echo $ev['image'] ? UPLOAD_URL . clean($ev['image']) : 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&q=80'; ?>" alt="<?php echo clean($ev['title']); ?>">
              <span class="badge"><?php echo clean($ev['category_name']); ?></span>
              <span class="price-badge <?php echo $ev['price'] == 0 ? 'free' : ''; ?>">
                <?php echo $ev['price'] == 0 ? 'FREE' : format_currency($ev['price']); ?>
              </span>
            </div>
            <div class="body">
              <h3><?php echo clean($ev['title']); ?></h3>
              <div class="meta">
                <span><i class="fa-regular fa-calendar"></i> <?php echo format_date($ev['event_date']); ?> · <?php echo format_time($ev['event_time']); ?></span>
                <span><i class="fa-solid fa-location-dot"></i> <?php echo clean($ev['venue']); ?></span>
                <span><i class="fa-solid fa-user-tie"></i> <?php echo clean($ev['organizer']); ?></span>
              </div>
              <div class="footer">
                <span class="seats"><?php echo (int) $ev['available_seats']; ?> seats left</span>
                <a href="<?php echo BASE_URL; ?>/event-details.php?slug=<?php echo urlencode($ev['slug']); ?>" class="btn btn-primary btn-sm">Book Now</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section" id="categories" style="background:var(--card); border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
  <div class="container">
    <div class="section-head"><div><h2>Browse by Category</h2><p>Find events that match your interests</p></div></div>
    <div class="grid">
      <?php foreach ($categories as $cat): ?>
        <a href="<?php echo BASE_URL; ?>/events.php?category=<?php echo $cat['id']; ?>" class="card card-hover" style="padding:28px; text-align:center;">
          <div style="width:56px; height:56px; margin:0 auto 14px; border-radius:16px; display:flex; align-items:center; justify-content:center; background:<?php echo clean($cat['color']); ?>22; color:<?php echo clean($cat['color']); ?>;">
            <i class="fa-solid <?php echo clean($cat['icon']); ?>" style="font-size:1.4rem;"></i>
          </div>
          <h3 style="font-size:1rem;"><?php echo clean($cat['name']); ?></h3>
          <p style="color:var(--muted); font-size:0.85rem; margin-top:4px;"><?php echo (int) $cat['event_count']; ?> events</p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><div><h2>What people are saying</h2><p>Trusted by event-goers everywhere</p></div></div>
    <div class="grid">
      <?php
      $testimonials = [
        ['Aditi Sharma', 'The booking process was seamless and the event was beautifully organized.', 'Attendee'],
        ['Rohan Mehta', 'Found a great networking event within minutes. Loved the interface!', 'Founder, Loopwave'],
        ['Priya Nair', 'Clear seat availability and instant confirmation — exactly what I needed.', 'Marketing Lead'],
      ];
      foreach ($testimonials as $t): ?>
        <div class="card" style="padding:28px;">
          <div style="color:var(--warning); margin-bottom:12px;"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
          <p style="color:var(--muted); margin-bottom:18px;">&ldquo;<?php echo clean($t[1]); ?>&rdquo;</p>
          <div style="font-weight:700;"><?php echo clean($t[0]); ?></div>
          <div style="font-size:0.85rem; color:var(--muted);"><?php echo clean($t[2]); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
