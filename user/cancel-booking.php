<?php
require_once __DIR__ . '/../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/user/my-bookings.php');
csrf_verify();

$pdo = getDBConnection();
$bookingId = (int) ($_POST['booking_id'] ?? 0);

try {
    $pdo->beginTransaction();

    // Ensure booking belongs to the logged-in user and lock the row
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? FOR UPDATE");
    $stmt->execute([$bookingId, current_user_id()]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $pdo->rollBack();
        set_flash('error', 'Booking not found.');
        redirect('/user/my-bookings.php');
    }

    if ($booking['status'] === 'cancelled') {
        $pdo->rollBack();
        set_flash('info', 'This booking is already cancelled.');
        redirect('/user/my-bookings.php');
    }

    $update = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?");
    $update->execute([$bookingId]);

    $restoreSeats = $pdo->prepare("UPDATE events SET available_seats = available_seats + ? WHERE id = ?");
    $restoreSeats->execute([$booking['seats_booked'], $booking['event_id']]);

    $pdo->commit();

    log_activity('user', current_user_id(), 'Cancelled booking ' . $booking['booking_code']);
    set_flash('success', 'Your booking has been cancelled.');
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Cancel booking error: ' . $e->getMessage());
    set_flash('error', 'Could not cancel the booking. Please try again.');
}

redirect('/user/my-bookings.php');
