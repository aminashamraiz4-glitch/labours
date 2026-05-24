<?php
session_start();
require_once "connection.php";

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Booking ID.");
}

 $booking_id = intval($_GET['id']);
 $customer_id = $_SESSION['user_id'];

// Fetch Booking
 $stmt = $conn->prepare("
   SELECT b.*, l.name as labour_name, l.skill 
   FROM bookings b
   JOIN labours l ON b.labour_id = l.labour_id
   WHERE b.booking_id = ? AND b.customer_id = ?
");
 $stmt->bind_param("ii", $booking_id, $customer_id);
 $stmt->execute();
 $result = $stmt->get_result();
 $booking = $result->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .status-card { background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 500px; padding: 40px; text-align: center; }
        
        /* Pulse Animation */
        .pulse-ring { display: block; width: 80px; height: 80px; border-radius: 50%; background: #ffc107; margin: 0 auto 20px auto; position: relative; animation: pulse 2s infinite; }
        .pulse-ring i { font-size: 40px; color: #000; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
    </style>
</head>
<body>

    <div class="status-card">
        <?php if ($booking['status'] === 'pending'): ?>
            <!-- PENDING STATE -->
            <div class="pulse-ring"><i class="bi bi-hourglass-split"></i></div>
            <h3 class="fw-bold mb-3">Request Pending</h3>
            <p class="text-muted mb-4">
                You have requested <strong><?= htmlspecialchars($booking['labour_name']) ?></strong> for <strong><?= date('d M, g:i A', strtotime($booking['start_time'])) ?></strong>.<br>
                Please wait for the labourer to accept.
            </p>
            <div class="spinner-border text-warning mb-2" role="status"><span class="visually-hidden">Checking...</span></div>
            <small class="text-muted d-block">Checking for updates automatically...</small>
            <script>setTimeout(function(){ window.location.reload(); }, 3000);</script>

        <?php elseif ($booking['status'] === 'booked'): ?>
            
            <?php if(empty($booking['customer_latitude']) || empty($booking['customer_longitude'])): ?>
                <!-- ================== ASK FOR LOCATION STATE ================== -->
                <div class="mb-3">
                    <i class="bi bi-geo-alt-fill text-primary" style="font-size: 4rem;"></i>
                </div>
                <h3 class="fw-bold mb-3">Booking Accepted!</h3>
                <p class="text-muted mb-4">
                    <strong><?= htmlspecialchars($booking['labour_name']) ?></strong> is on the way.<br>
                    We need your current location to help them find you.
                </p>
                
                <!-- Redirect to new Location Page -->
                <a href="booking_location.php?id=<?= $booking_id ?>" class="btn btn-primary btn-lg rounded-pill px-5">
                    Share Location Now
                </a>

            <?php else: ?>
                <!-- ================== CONFIRMED STATE ================== -->
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                </div>
                <h3 class="fw-bold text-success mb-3">All Set!</h3>
                <p class="text-muted mb-4">
                    Your location has been shared. <br>
                    See you at <strong><?= date('d M, g:i A', strtotime($booking['start_time'])) ?></strong>.
                </p>
                <a href="home.php" class="btn btn-primary rounded-pill px-5">Back to Home</a>
            <?php endif; ?>

        <?php elseif ($booking['status'] === 'cancelled'): ?>
            <div class="mb-4"><i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i></div>
            <h3 class="fw-bold text-danger mb-3">Request Cancelled</h3>
            <p class="text-muted mb-4">Unfortunately, <strong><?= htmlspecialchars($booking['labour_name']) ?></strong> declined this booking request.</p>
            <a href="home.php" class="btn btn-outline-danger rounded-pill px-5">Back to Home</a>
        <?php else: ?>
            <h3>Unknown Status</h3>
            <a href="home.php" class="btn btn-secondary">Go Home</a>
        <?php endif; ?>
    </div>

</body>
</html>