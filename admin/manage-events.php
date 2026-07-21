<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
$pdo = getDBConnection();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// ---- Handle create / update / delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_event') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = clean($_POST['title'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $shortDescription = clean($_POST['short_description'] ?? '');
        $venue = clean($_POST['venue'] ?? '');
        $organizer = clean($_POST['organizer'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $eventTime = $_POST['event_time'] ?? '';
        $totalSeats = (int) ($_POST['total_seats'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['upcoming','ongoing','completed','cancelled']) ? $_POST['status'] : 'upcoming';
        $rules = trim($_POST['rules'] ?? '');
        $mapUrl = trim($_POST['map_embed_url'] ?? '');

        $errors = [];
        if ($title === '' || strlen($title) < 3) $errors[] = 'Title is required.';
        if ($categoryId <= 0) $errors[] = 'Please select a category.';
        if ($description === '') $errors[] = 'Description is required.';
        if ($venue === '') $errors[] = 'Venue is required.';
        if (!$eventDate || !$eventTime) $errors[] = 'Date and time are required.';
        if ($totalSeats <= 0) $errors[] = 'Total seats must be greater than 0.';

        $imageFilename = null;
        if (!empty($_FILES['image']['name'])) {
            $uploaded = handle_image_upload('image');
            if ($uploaded === false) {
                $errors[] = 'Event image must be JPG, PNG, or WEBP under 5MB.';
            } else {
                $imageFilename = $uploaded;
            }
        }

        if (empty($errors)) {
            if ($id > 0) {
                // Update — preserve available_seats delta proportionally
                $existing = $pdo->prepare("SELECT total_seats, available_seats, image FROM events WHERE id = ?");
                $existing->execute([$id]);
                $ex = $existing->fetch();
                $bookedSeats = $ex['total_seats'] - $ex['available_seats'];
                $newAvailable = max(0, $totalSeats - $bookedSeats);
                $finalImage = $imageFilename ?: $ex['image'];

                $stmt = $pdo->prepare("UPDATE events SET category_id=?, title=?, description=?, short_description=?,
                    venue=?, organizer=?, event_date=?, event_time=?, total_seats=?, available_seats=?, price=?,
                    status=?, rules=?, map_embed_url=?, image=? WHERE id=?");
                $stmt->execute([$categoryId, $title, $description, $shortDescription, $venue, $organizer,
                    $eventDate, $eventTime, $totalSeats, $newAvailable, $price, $status, $rules, $mapUrl, $finalImage, $id]);

                log_activity('admin', $_SESSION['admin_id'], 'Updated event: ' . $title);
                set_flash('success', 'Event updated successfully.');
            } else {
                $slug = generate_slug($title);
                $stmt = $pdo->prepare("INSERT INTO events (category_id, title, slug, description, short_description,
                    venue, organizer, event_date, event_time, total_seats, available_seats, price, status, rules,
                    map_embed_url, image, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$categoryId, $title, $slug, $description, $shortDescription, $venue, $organizer,
                    $eventDate, $eventTime, $totalSeats, $totalSeats, $price, $status, $rules, $mapUrl,
                    $imageFilename, $_SESSION['admin_id']]);

                log_activity('admin', $_SESSION['admin_id'], 'Created event: ' . $title);
                set_flash('success', 'Event created successfully.');
            }
        } else {
            set_flash('error', implode(' ', $errors));
        }
        redirect('/admin/manage-events.php');
    }

    if ($action === 'delete_event') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        log_activity('admin', $_SESSION['admin_id'], 'Deleted event ID ' . $id);
        set_flash('success', 'Event deleted.');
        redirect('/admin/manage-events.php');
    }
}

$search = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($search !== '') {
    $where = "e.title LIKE ?";
    $params[] = "%$search%";
}
$stmt = $pdo->prepare("SELECT e.*, c.name AS category_name FROM events e
    JOIN categories c ON c.id = e.category_id WHERE $where ORDER BY e.created_at DESC");
$stmt->execute($params);
$events = $stmt->fetchAll();

$pageTitle = 'Manage Events';
include __DIR__ . '/includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
  <form method="GET" style="display:flex; gap:8px;">
    <input type="text" name="q" class="form-control" placeholder="Search events..." value="<?php echo clean($search); ?>" style="width:260px;">
    <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-search"></i></button>
  </form>
  <button class="btn btn-primary" onclick="openEventModal()"><i class="fa-solid fa-plus"></i> Add Event</button>
</div>

<div class="card data-table-wrap">
  <table class="styled-table dt-table" style="width:100%;">
    <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Date</th><th>Seats</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($events as $ev): ?>
      <tr>
        <td><img class="thumb-sm" src="<?php echo $ev['image'] ? UPLOAD_URL . clean($ev['image']) : 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=100&q=60'; ?>"></td>
        <td><?php echo clean($ev['title']); ?></td>
        <td><?php echo clean($ev['category_name']); ?></td>
        <td><?php echo format_date($ev['event_date']); ?></td>
        <td><?php echo (int) $ev['available_seats']; ?> / <?php echo (int) $ev['total_seats']; ?></td>
        <td><?php echo $ev['price'] == 0 ? 'Free' : format_currency($ev['price']); ?></td>
        <td><span class="status-pill status-<?php echo $ev['status']; ?>"><?php echo ucfirst($ev['status']); ?></span></td>
        <td style="white-space:nowrap;">
          <button class="btn btn-outline btn-sm" onclick='openEventModal(<?php echo json_encode($ev); ?>)'><i class="fa-solid fa-pen"></i></button>
          <form method="POST" style="display:inline;" onsubmit="return confirmAction(this, 'Delete this event?', 'This cannot be undone and will remove related bookings.');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete_event">
            <input type="hidden" name="id" value="<?php echo $ev['id']; ?>">
            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal-overlay" id="eventModal">
  <div class="modal-box">
    <h3 style="margin-bottom:20px;" id="eventModalTitle">Add Event</h3>
    <form method="POST" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="save_event">
      <input type="hidden" name="id" id="f_id" value="">
      <div class="form-group"><label>Title</label><input type="text" name="title" id="f_title" class="form-control" required></div>
      <div class="form-group"><label>Category</label>
        <select name="category_id" id="f_category" class="form-control" required>
          <?php foreach ($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo clean($cat['name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Short Description</label><input type="text" name="short_description" id="f_short_desc" class="form-control"></div>
      <div class="form-group"><label>Full Description</label><textarea name="description" id="f_description" class="form-control" required></textarea></div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div class="form-group"><label>Venue</label><input type="text" name="venue" id="f_venue" class="form-control" required></div>
        <div class="form-group"><label>Organizer</label><input type="text" name="organizer" id="f_organizer" class="form-control"></div>
        <div class="form-group"><label>Date</label><input type="date" name="event_date" id="f_date" class="form-control" required></div>
        <div class="form-group"><label>Time</label><input type="time" name="event_time" id="f_time" class="form-control" required></div>
        <div class="form-group"><label>Total Seats</label><input type="number" name="total_seats" id="f_seats" min="1" class="form-control" required></div>
        <div class="form-group"><label>Price (₹)</label><input type="number" name="price" id="f_price" min="0" step="0.01" class="form-control" required></div>
      </div>
      <div class="form-group"><label>Status</label>
        <select name="status" id="f_status" class="form-control">
          <option value="upcoming">Upcoming</option><option value="ongoing">Ongoing</option>
          <option value="completed">Completed</option><option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="form-group"><label>Google Maps Embed URL (optional)</label><input type="text" name="map_embed_url" id="f_map" class="form-control"></div>
      <div class="form-group"><label>Rules (optional)</label><textarea name="rules" id="f_rules" class="form-control"></textarea></div>
      <div class="form-group"><label>Event Image</label><input type="file" name="image" accept="image/*" class="form-control"></div>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="btn btn-outline" onclick="closeEventModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Event</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEventModal(ev) {
  document.getElementById('eventModal').classList.add('open');
  const set = (id, val) => document.getElementById(id).value = val ?? '';
  if (ev) {
    document.getElementById('eventModalTitle').textContent = 'Edit Event';
    set('f_id', ev.id); set('f_title', ev.title); set('f_category', ev.category_id);
    set('f_short_desc', ev.short_description); set('f_description', ev.description);
    set('f_venue', ev.venue); set('f_organizer', ev.organizer);
    set('f_date', ev.event_date); set('f_time', ev.event_time);
    set('f_seats', ev.total_seats); set('f_price', ev.price);
    set('f_status', ev.status); set('f_map', ev.map_embed_url); set('f_rules', ev.rules);
  } else {
    document.getElementById('eventModalTitle').textContent = 'Add Event';
    document.querySelector('#eventModal form').reset();
    set('f_id', '');
  }
}
function closeEventModal() { document.getElementById('eventModal').classList.remove('open'); }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
