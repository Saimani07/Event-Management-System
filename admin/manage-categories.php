<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_category') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = clean($_POST['name'] ?? '');
        $icon = clean($_POST['icon'] ?? 'fa-star');
        $color = clean($_POST['color'] ?? '#2563EB');

        if ($name === '') {
            set_flash('error', 'Category name is required.');
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE categories SET name=?, icon=?, color=? WHERE id=?");
                $stmt->execute([$name, $icon, $color, $id]);
                set_flash('success', 'Category updated.');
            } else {
                $slug = generate_slug($name);
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, color) VALUES (?,?,?,?)");
                try {
                    $stmt->execute([$name, $slug, $icon, $color]);
                    set_flash('success', 'Category created.');
                } catch (PDOException $e) {
                    set_flash('error', 'A category with this name may already exist.');
                }
            }
        }
        redirect('/admin/manage-categories.php');
    }

    if ($action === 'delete_category') {
        $id = (int) ($_POST['id'] ?? 0);
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM events WHERE category_id = ?");
        $inUse->execute([$id]);
        if ($inUse->fetchColumn() > 0) {
            set_flash('error', 'Cannot delete a category that has events assigned to it.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('success', 'Category deleted.');
        }
        redirect('/admin/manage-categories.php');
    }
}

$categories = $pdo->query("SELECT c.*, COUNT(e.id) AS event_count FROM categories c
    LEFT JOIN events e ON e.category_id = c.id GROUP BY c.id ORDER BY c.name")->fetchAll();

$pageTitle = 'Manage Categories';
include __DIR__ . '/includes/header.php';
?>

<div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
  <button class="btn btn-primary" onclick="openCatModal()"><i class="fa-solid fa-plus"></i> Add Category</button>
</div>

<div class="grid">
  <?php foreach ($categories as $cat): ?>
    <div class="card" style="padding:24px;">
      <div style="display:flex; align-items:center; gap:14px; margin-bottom:14px;">
        <div style="width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:<?php echo clean($cat['color']); ?>22; color:<?php echo clean($cat['color']); ?>;">
          <i class="fa-solid <?php echo clean($cat['icon']); ?>"></i>
        </div>
        <div>
          <div style="font-weight:700;"><?php echo clean($cat['name']); ?></div>
          <div style="font-size:0.8rem; color:var(--muted);"><?php echo (int) $cat['event_count']; ?> events</div>
        </div>
      </div>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-outline btn-sm" style="flex:1;" onclick='openCatModal(<?php echo json_encode($cat); ?>)'>Edit</button>
        <form method="POST" style="flex:1;" onsubmit="return confirmAction(this, 'Delete this category?');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="delete_category">
          <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
          <button type="submit" class="btn btn-danger btn-sm btn-block">Delete</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="modal-overlay" id="catModal">
  <div class="modal-box" style="max-width:420px;">
    <h3 style="margin-bottom:20px;" id="catModalTitle">Add Category</h3>
    <form method="POST">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="save_category">
      <input type="hidden" name="id" id="c_id">
      <div class="form-group"><label>Name</label><input type="text" name="name" id="c_name" class="form-control" required></div>
      <div class="form-group"><label>Font Awesome Icon (e.g. fa-music)</label><input type="text" name="icon" id="c_icon" class="form-control" value="fa-star" required></div>
      <div class="form-group"><label>Color</label><input type="color" name="color" id="c_color" class="form-control" value="#2563EB" style="height:44px;"></div>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="btn btn-outline" onclick="closeCatModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCatModal(cat) {
  document.getElementById('catModal').classList.add('open');
  const set = (id, val) => document.getElementById(id).value = val ?? '';
  if (cat) {
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    set('c_id', cat.id); set('c_name', cat.name); set('c_icon', cat.icon); set('c_color', cat.color);
  } else {
    document.getElementById('catModalTitle').textContent = 'Add Category';
    document.querySelector('#catModal form').reset();
    set('c_id', '');
  }
}
function closeCatModal() { document.getElementById('catModal').classList.remove('open'); }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
