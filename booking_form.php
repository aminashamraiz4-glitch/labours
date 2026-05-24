<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "connection.php";

// ============================================================
// 1. PHPMAILER SETUP (UPDATED FOR YOUR FOLDER STRUCTURE)
// ============================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Since your folder is 'includes/phpmailer', we include the files like this:
require 'includes/phpmailer/src/Exception.php';
require 'includes/phpmailer/src/PHPMailer.php';
require 'includes/phpmailer/src/SMTP.php';

// ============================================================
// 2. EMAIL CONFIGURATION (CHANGE THIS PART!)
// ============================================================
 $mail_config = [
    'host' => 'smtp.gmail.com',             // Usually smtp.gmail.com
    'port' => 587,                          // 587 for TLS
    'username' => 'support.trustedlabour@gmail.com',   
    'password' => 'ejns sidp xnnr wocl',     
    'from_name' => 'Labour Booking System',
    'from_email' => 'noreply@yourwebsite.com'
];

// Function to send email
function sendBookingEmail($labourEmail, $labourName, $bookingDetails) {
    global $mail_config;
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = $mail_config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mail_config['username'];
        $mail->Password   = $mail_config['password'];
        $mail->SMTPSecure = 'TLS'; 
        $mail->Port       = $mail_config['port'];

        $mail->setFrom($mail_config['from_email'], $mail_config['from_name']);
        $mail->addAddress($labourEmail, $labourName);

        $mail->isHTML(true);
        $mail->Subject = 'New Booking Request - Action Required';

        // The 5 Minute Warning Message
        $mail->Body    = "
            <h2>New Booking Request</h2>
            <p>Dear <strong>{$labourName}</strong>,</p>
            <p>You have received a new booking request for <strong>{$bookingDetails['date']}</strong> from <strong>{$bookingDetails['start']}</strong> to <strong>{$bookingDetails['end']}</strong>.</p>
            
            <div style='background-color: #fff3cd; padding: 15px; border-left: 6px solid #ffc107; margin: 20px 0;'>
                <h3 style='margin-top:0; color: #856404;'>⚠️ Time Sensitive</h3>
                <p style='margin-bottom:0; color: #856404;'>Please check your dashboard to <strong>Accept</strong> or <strong>Reject</strong> this request immediately.</p>
                <p style='color: #d63384; font-weight: bold;'>Note: This request will be automatically cancelled if no action is taken within <strong>5 minutes</strong>.</p>
            </div>

            <p>Log in to your account to manage bookings.</p>
        ";

        $mail->AltBody = "You have a new booking request. Please accept or reject within 5 minutes.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        // If email fails, we return false so the booking still saves
        return false;
    }
}

// ============================================================
// 3. AUTH & VALIDATION
// ============================================================
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

 $customer_id = $_SESSION['user_id'];

if (!isset($_GET['labour_id']) || !is_numeric($_GET['labour_id'])) {
    die("Invalid labour selection.");
}

 $labour_id = intval($_GET['labour_id']);

// Fetch labour details (email is included)
 $stmt = $conn->prepare("SELECT name, location, skill, email FROM labours WHERE labour_id = ?");
 $stmt->bind_param("i", $labour_id);
 $stmt->execute();
 $result = $stmt->get_result();
 $labour = $result->fetch_assoc();

if (!$labour) {
    die("Labour not found.");
}

// ============================================================
// 4. BOOKING LOGIC
// ============================================================
 $modal_status = [
    'show' => false,
    'type' => '', 
    'title' => '',
    'message' => '',
    'booking_id' => null
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_date = trim($_POST['booking_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);

    if (empty($booking_date) || empty($start_time) || empty($end_time)) {
        $modal_status = [
            'show' => true,
            'type' => 'error',
            'title' => 'Missing Information',
            'message' => 'All fields are required.'
        ];
    } else {
        $start_datetime = date('Y-m-d H:i:s', strtotime("$booking_date $start_time"));
        $end_datetime   = date('Y-m-d H:i:s', strtotime("$booking_date $end_time"));

        // 30 MINUTE BUFFER
        $current_time = new DateTime();
        $current_time->add(new DateInterval('PT30M')); 
        $min_allowed_time = $current_time->format('Y-m-d H:i:s');

        if ($start_datetime < $min_allowed_time) {
            $modal_status = [
                'show' => true,
                'type' => 'error',
                'title' => 'Booking Too Soon',
                'message' => 'Bookings must be made at least 30 minutes from now.'
            ];
        } elseif ($end_datetime <= $start_datetime) {
            $modal_status = [
                'show' => true,
                'type' => 'error',
                'title' => 'Invalid Time',
                'message' => 'End time must be after start time.'
            ];
        } else {
            // Check for overlapping bookings
            $check_stmt = $conn->prepare("
                SELECT * FROM bookings 
                WHERE labour_id = ? 
                  AND status != 'cancelled' 
                  AND (
                        (start_time <= ? AND end_time > ?) OR
                        (start_time < ? AND end_time >= ?) OR
                        (start_time >= ? AND end_time <= ?)
                      )
            ");
            $check_stmt->bind_param(
                "issssss",
                $labour_id,
                $start_datetime,
                $start_datetime,
                $end_datetime,
                $end_datetime,
                $start_datetime,
                $end_datetime
            );
            $check_stmt->execute();
            $conflict = $check_stmt->get_result();

            if ($conflict->num_rows > 0) {
                $modal_status = [
                    'show' => true,
                    'type' => 'error',
                    'title' => 'Slot Unavailable',
                    'message' => 'This labour is already booked during the selected time.'
                ];
            } else {
                // INSERT BOOKING WITH STATUS 'pending'
                $stmt = $conn->prepare("
                    INSERT INTO bookings (labour_id, customer_id, booking_date, start_time, end_time, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param("iisss", $labour_id, $customer_id, $booking_date, $start_datetime, $end_datetime);

                if ($stmt->execute()) {
                    $new_booking_id = $conn->insert_id;

                    // SEND EMAIL HERE
                    $booking_info = [
                        'date' => $booking_date,
                        'start' => $start_time,
                        'end' => $end_time
                    ];
                    
                    // Send the email using the function defined above
                    sendBookingEmail($labour['email'], $labour['name'], $booking_info);

                    $modal_status = [
                        'show' => true,
                        'type' => 'success',
                        'title' => 'Request Sent!',
                        'message' => 'Your request has been sent to the labour. They have 5 minutes to respond.',
                        'booking_id' => $new_booking_id
                    ];
                } else {
                    $modal_status = [
                        'show' => true,
                        'type' => 'error',
                        'title' => 'System Error',
                        'message' => 'Could not complete booking. Please try again.'
                    ];
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Labour - <?= htmlspecialchars($labour['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: 'Segoe UI', sans-serif; }
        .booking-card { background: #fff; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 480px; overflow: hidden; }
        .card-header { background-color: #ffc107; padding: 25px; text-align: center; color: #000; }
        .card-body { padding: 30px; }
        .form-control:focus { border-color: #ffc107; box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25); }
        .input-group-text { background-color: #f8f9fa; color: #495057; }
        .btn-book { background-color: #000; color: #fff; padding: 14px; font-weight: 600; border-radius: 12px; width: 100%; border: none; transition: 0.3s; }
        .btn-book:hover { background-color: #333; transform: translateY(-2px); }
        .modal-content { border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="booking-card">
        <div class="card-header">
            <h3><?= htmlspecialchars($labour['name']) ?></h3>
            <p><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($labour['location']) ?></p>
        </div>
        <div class="card-body">
            <form method="POST" id="bookingForm">
                <div class="mb-3"><div class="input-group"><span class="input-group-text"><i class="bi bi-calendar-event"></i></span><input type="date" class="form-control" name="booking_date" id="booking_date" required></div></div>
                <div class="row">
                    <div class="col-6 mb-3"><div class="input-group"><span class="input-group-text"><i class="bi bi-clock-history"></i></span><input type="time" class="form-control" name="start_time" id="start_time" required></div><small class="text-muted">Start (30m buffer)</small></div>
                    <div class="col-6 mb-3"><div class="input-group"><span class="input-group-text"><i class="bi bi-clock"></i></span><input type="time" class="form-control" name="end_time" id="end_time" required></div><small class="text-muted">End</small></div>
                </div>
                <button type="submit" class="btn-book" id="submitBtn"><span class="normal-text">Send Request</span></button>
            </form>
        </div>
    </div>

    <!-- MODAL -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <div class="modal-body">
                    <div id="modalIcon"></div>
                    <h4 id="modalTitle" class="fw-bold mb-3"></h4>
                    <p id="modalMessage" class="text-muted mb-4"></p>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="modalActionBtn">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dateInput = document.getElementById('booking_date'), startTimeInput = document.getElementById('start_time'), endTimeInput = document.getElementById('end_time');
        function setMinDateTime() {
            const now = new Date(), todayStr = now.toISOString().split('T')[0];
            dateInput.min = todayStr;
            if (dateInput.value === todayStr) {
                const thirtyMinsLater = new Date(now.getTime() + 30 * 60000);
                startTimeInput.min = `${String(thirtyMinsLater.getHours()).padStart(2,'0')}:${String(thirtyMinsLater.getMinutes()).padStart(2,'0')}`;
            } else startTimeInput.min = '00:00';
        }
        dateInput.addEventListener('change', setMinDateTime);
        startTimeInput.addEventListener('change', () => { endTimeInput.min = startTimeInput.value; });
        setMinDateTime();

        const modalStatus = {
            show: <?= $modal_status['show'] ? 'true' : 'false' ?>,
            type: "<?= $modal_status['type'] ?>",
            title: "<?= $modal_status['title'] ?>",
            message: "<?= $modal_status['message'] ?>",
            bookingId: <?= $modal_status['booking_id'] ?: 'null' ?>
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (modalStatus.show && (modalStatus.type === 'success' || modalStatus.type === 'error')) {
                const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
                const iconContainer = document.getElementById('modalIcon');
                document.getElementById('modalTitle').innerText = modalStatus.title;
                document.getElementById('modalMessage').innerText = modalStatus.message;
                
                if (modalStatus.type === 'success') {
                    iconContainer.innerHTML = '<i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>';
                    document.getElementById('modalActionBtn').className = 'btn btn-success rounded-pill px-4';
                } else {
                    iconContainer.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 3rem;"></i>';
                    document.getElementById('modalActionBtn').className = 'btn btn-danger rounded-pill px-4';
                }

                document.getElementById('modalActionBtn').onclick = function() {
                    statusModal.hide();
                    if (modalStatus.type === 'success') {
                        window.location.href = 'booking_pending.php?id=' + modalStatus.bookingId;
                    }
                };
                statusModal.show();
            }
        });
    </script>
</body>
</html>