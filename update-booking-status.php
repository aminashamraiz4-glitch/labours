<?php
session_start();
include 'connection.php';

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_type'] !== 'customer'
) {
    header("Location: login.php");
    exit;
}

if ($_POST['payment_method'] === 'cash') {
    $booking_id = (int)$_POST['booking_id'];

    $query = "UPDATE bookings SET status='completed' WHERE booking_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();

    header("Location: my-bookings.php");
    exit;
}
