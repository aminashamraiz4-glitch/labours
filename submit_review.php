<?php
session_start();
require_once "connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$booking_id  = $_POST['booking_id'];
$labour_id   = $_POST['labour_id'];
$rating      = $_POST['rating'];
$review_text = $_POST['review_text'];

// Insert review
$sql = "INSERT INTO reviews (booking_id, customer_id, labour_id, rating, review_text)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiis", $booking_id, $customer_id, $labour_id, $rating, $review_text);

if ($stmt->execute()) {
    echo "<script>alert('Review submitted!'); window.location='profile.php';</script>";
} else {
    echo "Error adding review.";
}
