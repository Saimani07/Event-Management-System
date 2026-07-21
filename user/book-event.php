<?php
require_once __DIR__ . '/../config/config.php';
require_login();
$pdo = getDBConnection();

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM events WHERE slug = ?");
$stmt->execute([$slug]);
$event = $stmt->fetch();

if (!$event) { redirect('/events.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $seats = max(1, (int) ($_POST['seats'] ?? 1));
    $name  = clean($_POST['attendee_name'] ?? '');
    $email = filter_var(trim($_POST['attendee_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = clean($_POST['attendee_phone'] ?? '');

    if ($name === '') $errors[] = 'Attendee name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors[] = 'A valid phone number is required.';
    if ($seats < 1 || $seats > 10) $errors[] = 'You can book between 1 and 10 seats at a time.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Lock the row to prevent race conditions on seat availability
            $lockStmt = $pdo->prepare("SELECT available_seats, price FROM events WHERE id = ? FOR UPDATE");
            $lockStmt->execute([$event['id']]);
            $locked = $lockStmt->fetch();

            if (!$locked || $locked['available_seats'] < $seats) {
                $pdo->rollBack();
                $errors[] = 'Not enough seats available. Please choose a smaller quantity.';
            } else {
                $totalAmount = $locked['price'] * $seats;
                $bookingCode = generate_booking_code();

                $insert = $pdo->prepare("INSERT INTO bookings
                    (booking_code, user_id, event_id, seats_booked, total_amount, attendee_name, attendee_email, attendee_phone, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
                $insert->execute([$bookingCode, current_user_id(), $event['id'], $seats, $totalAmount, $name, $email, $phone]);

                $update = $pdo->prepare("UPDATE events SET available_seats = available_seats - ? WHERE id = ?");
                $update->execute([$seats, $event['id']]);

                $pdo->commit();

                log_activity('user', current_user_id(), 'Booked event: ' . $event['title'] . ' (' . $bookingCode . ')');
                set_flash('success', 'Booking confirmed! Your booking ID is ' . $bookingCode);
                redirect('/user/my-bookings.php');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Booking error: ' . $e->getMessage());
            $errors[] = 'Something went wrong while processing your booking. Please try again.';
        }
    }
}

$pageTitle = 'Book: ' . $event['title'];
include __DIR__ . '/../includes/header.php';
?>

<section class="section" style="max-width:640px; margin:0 auto;">
  <div class="container">
    <div class="card glass" style="padding:36px;">
      <h2 style="margin-bottom:6px;">Book: <?php echo clean($event['title']); ?></h2>
      <p style="color:var(--muted); margin-bottom:24px;"><?php echo format_date($event['event_date']); ?> · <?php echo format_time($event['event_time']); ?> · <?php echo clean($event['venue']); ?></p>

      <?php if (!empty($errors)): ?>
        <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#B91C1C; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.88rem;">
          <?php foreach ($errors as $e) echo '<div>' . clean($e) . '</div>'; ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label>Number of Seats</label>
          <input type="number" name="seats" min="1" max="<?php echo min(10, $event['available_seats']); ?>" value="1" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Attendee Name</label>
          <input type="text" name="attendee_name" class="form-control" value="<?php echo clean($_SESSION['user_name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="attendee_email" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="attendee_phone" class="form-control" required>
        </div>
        <div style="display:flex; justify-content:space-between; padding:16px 0; border-top:1px solid var(--border); margin-bottom:20px;">
          <span style="font-weight:600;">Price per seat</span>
          <span><?php echo $event['price'] == 0 ? 'Free' : format_currency($event['price']); ?></span>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Confirm Booking</button>
      </form>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
