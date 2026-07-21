<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'All Events';
$pdo = getDBConnection();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// ---- Filters ----
$search   = trim($_GET['q'] ?? '');
$category = (int) ($_GET['category'] ?? 0);
$priceFilter = $_GET['price'] ?? '';
$sort     = $_GET['sort'] ?? 'upcoming';
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 6;

$where = ["e.status IN ('upcoming','ongoing')"];
$params = [];

if ($search !== '') {
    $where[] = "(e.title LIKE ? OR e.venue LIKE ? OR e.organizer LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
if ($category > 0) {
    $where[] = "e.category_id = ?";
    $params[] = $category;
}
if ($priceFilter === 'free') {
    $where[] = "e.price = 0";
} elseif ($priceFilter === 'paid') {
    $where[] = "e.price > 0";
}

$orderBy = match ($sort) {
    'newest'   => 'e.created_at DESC',
    'oldest'   => 'e.created_at ASC',
    'price_low'  => 'e.price ASC',
    'price_high' => 'e.price DESC',
    default    => 'e.event_date ASC',
};

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM events e WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT e.*, c.name AS category_name, c.color AS category_color
        FROM events e JOIN categories c ON c.id = e.category_id
        WHERE $whereSql ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:48px;">
  <div class="container">
    <div class="section-head">
      <div><h2>All Events</h2><p><?php echo $total; ?> events found</p></div>
    </div>

    <form method="GET" class="card glass" style="padding:20px; margin-bottom:32px; display:grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap:14px; align-items:end;">
      <div class="form-group" style="margin:0;">
        <label>Search</label>
        <input type="text" name="q" class="form-control" placeholder="Name, venue, organizer..." value="<?php echo clean($search); ?>">
      </div>
      <div class="form-group" style="margin:0;">
        <label>Category</label>
        <select name="category" class="form-control">
          <option value="0">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo $category === (int) $cat['id'] ? 'selected' : ''; ?>><?php echo clean($cat['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label>Price</label>
        <select name="price" class="form-control">
          <option value="">Any</option>
          <option value="free" <?php echo $priceFilter === 'free' ? 'selected' : ''; ?>>Free</option>
          <option value="paid" <?php echo $priceFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label>Sort By</label>
        <select name="sort" class="form-control">
          <option value="upcoming" <?php echo $sort === 'upcoming' ? 'selected' : ''; ?>>Upcoming First</option>
          <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
          <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
          <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
          <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>

    <?php if (empty($events)): ?>
      <div class="empty-state"><i class="fa-solid fa-magnifying-glass"></i><p>No events match your search. Try different filters.</p></div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($events as $ev): ?>
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
              </div>
              <div class="footer">
                <span class="seats"><?php echo (int) $ev['available_seats']; ?> seats left</span>
                <a href="<?php echo BASE_URL; ?>/event-details.php?slug=<?php echo urlencode($ev['slug']); ?>" class="btn btn-primary btn-sm">View</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:36px;">
          <?php for ($i = 1; $i <= $totalPages; $i++):
            $qs = $_GET; $qs['page'] = $i; ?>
            <a href="?<?php echo http_build_query($qs); ?>" class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm"><?php echo $i; ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
