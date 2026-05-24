<?php
session_start();
include 'connection.php';

/* HARD STOP: only labour allowed */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'labour') {
    header("Location: login.php");
    exit;
}

$labour_id = $_SESSION['user_id'];
$message = "";

// 1. HANDLE ACCEPT / REJECT / MARK COMPLETED ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $action = $_POST['action']; // 'accept', 'reject', or 'mark_completed'

    $new_status = "";

    // Update Logic
    if ($action === 'accept') {
        $new_status = 'booked';
        $message = "Booking Accepted!";
    } elseif ($action === 'reject') {
        $new_status = 'cancelled';
        $message = "Booking Rejected.";
    } elseif ($action === 'mark_completed') {
        // Use a specific status for verification to not conflict with initial 'pending' requests
        $new_status = 'pending_verification';
        $message = "Marked as completed. Waiting for customer verification.";
    }

    if ($new_status) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ? AND labour_id = ?");
        $stmt->bind_param("sii", $new_status, $booking_id, $labour_id);

        if ($stmt->execute()) {
            // Refresh to show changes
            echo "<script>window.location.href='labour-my-bookings.php';</script>";
            exit;
        }
    }
}

/* Fetch bookings + customer details + created_at (needed for timer) */
$query = "
    SELECT 
        b.booking_id,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.status,
        b.created_at,
        b.customer_latitude,
        b.customer_longitude, 
        c.name AS customer_name,
        c.location AS customer_location
    FROM bookings b
    INNER JOIN customers c ON b.customer_id = c.customer_id
    WHERE b.labour_id = ?
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $labour_id);
$stmt->execute();
$result = $stmt->get_result();

// ============================================================
// 2. SEPARATE BOOKINGS INTO "NEW" AND "HISTORY"
// ============================================================
$new_requests = [];
$past_bookings = [];

while ($row = $result->fetch_assoc()) {
    $created_time = strtotime($row['created_at']);
    $time_diff = time() - $created_time; // Seconds passed since request

    // Is it a NEW request? (Pending AND less than 5 minutes/300 seconds)
    if ($row['status'] === 'pending' && $time_diff < 300) {
        $new_requests[] = $row;
    } else {
        // Everything else goes to history
        $past_bookings[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Bookings</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Leaflet CSS (For Map) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin="" />

    <style>
        /* ===== Page ===== */
        body {
            background-color: #f8f9fa;
        }

        .page-title {
            font-size: 2.3rem;
            font-weight: 600;
            margin-bottom: 2.5rem;
        }

        .section-header {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 40px 0 20px 0;
            color: #495057;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }

        /* ===== Booking Card (Base) ===== */
        .booking-card {
            border-radius: 14px;
            transition: all 0.3s ease;
            background: #fff;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .booking-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        /* ===== NEW REQUEST CARD STYLE ===== */
        .booking-card-new {
            border-left: 6px solid #E1AD01 !important;
            /* Mustard Border */
            background: linear-gradient(to right, #fff, #fffbf0);
        }

        .booking-card-new .booking-customer {
            color: #E1AD01;
        }

        /* ===== Content ===== */
        .booking-body {
            padding: 1.6rem;
        }

        .booking-customer {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: .4rem;
        }

        .booking-location {
            color: #6c757d;
            font-size: .95rem;
            margin-bottom: 1rem;
        }

        .booking-meta p {
            margin-bottom: .3rem;
            font-size: .95rem;
        }

        /* ===== Status Badge ===== */
        .status-badge {
            display: inline-block;
            padding: .35rem .8rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-booked {
            background: #ffc107;
            color: #212529;
        }

        .status-completed {
            background: #28a745;
            color: #fff;
        }

        .status-cancelled {
            background: #dc3545;
            color: #fff;
        }

        .status-expired {
            background: #e9ecef;
            color: #6c757d;
        }

        .status-pending-verification {
            background: #17a2b8;
            color: #fff;
        }

        /* ===== Action Buttons ===== */
        .btn-action {
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-accept {
            background: #25D366;
            color: white;
            border: none;
        }

        .btn-accept:hover {
            background: #1da851;
            color: white;
        }

        .btn-reject {
            background: white;
            color: #FA5252;
            border: 1px solid #FA5252;
        }

        .btn-reject:hover {
            background: #FA5252;
            color: white;
        }

        .btn-complete {
            background: #E1AD01;
            color: #212529;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            width: auto;
            margin-top: 10px;
        }

        .btn-complete:hover {
            background: #c49400;
            color: #fff;
        }

        /* --- NEW: Location Button --- */
        .btn-view-loc {
            background-color: #e7f1ff;
            color: #0d6efd;
            border: 1px solid #0d6efd;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-view-loc:hover {
            background-color: #0d6efd;
            color: white;
        }

        /* Timer Text */
        .timer-text {
            color: #d63384;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .waiting-text {
            color: #0d6efd;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
            display: block;
            text-align: center;
        }

        /* --- CUSTOM MODAL STYLES --- */
        .confirm-modal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .confirm-modal .modal-header {
            border-bottom: none;
            padding-top: 2rem;
            padding-bottom: 0;
        }

        .confirm-modal .modal-body {
            padding: 1rem 2rem 2rem 2rem;
            text-align: center;
        }

        .confirm-modal .modal-footer {
            border-top: none;
            padding-bottom: 2rem;
            justify-content: center;
            gap: 15px;
        }

        .modal-icon-container {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            transition: all 0.3s ease;
        }

        /* Specific Modal Colors */
        .type-accept .modal-icon-container {
            background-color: #d1e7dd;
            color: #198754;
        }

        .type-accept .btn-confirm {
            background-color: #198754;
            color: white;
            border: none;
        }

        .type-reject .modal-icon-container {
            background-color: #f8d7da;
            color: #dc3545;
        }

        .type-reject .btn-confirm {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .type-complete .modal-icon-container {
            background-color: #fff9db;
            color: #E1AD01;
        }

        .type-complete .btn-confirm {
            background-color: #E1AD01;
            color: #212529;
            border: none;
        }

        .type-complete .btn-confirm:hover {
            background-color: #c49400;
            color: #fff;
        }

        .btn-cancel-custom {
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
            padding: 0.6rem 2rem;
            border-radius: 12px;
            font-weight: 600;
        }

        .btn-cancel-custom:hover {
            background-color: #e2e6ea;
        }

        .btn-confirm {
            padding: 0.6rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.1s;
        }

        .btn-confirm:active {
            transform: scale(0.95);
        }

        /* Map Modal Specifics */
        #locationMap {
            height: 300px;
            width: 100%;
            border-radius: 10px;
            margin-bottom: 10px;
            z-index: 1;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container my-5">
        <h2 class="text-center page-title">My Bookings</h2>

        <?php if ($message): ?>
            <div class="alert alert-success text-center"><?= $message ?></div>
        <?php endif; ?>

        <!-- SECTION 1: NEW REQUESTS -->
        <?php if (!empty($new_requests)): ?>
            <div class="section-header"><i class="bi bi-bell-fill text-warning"></i> New Requests</div>

            <?php foreach ($new_requests as $row):
                $created_time = strtotime($row['created_at']);
                $time_diff = time() - $created_time;
                $seconds_left = 300 - $time_diff;
            ?>
                <div class="card booking-card booking-card-new mb-3">
                    <div class="booking-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div>
                                <div class="booking-customer"><?= htmlspecialchars($row['customer_name']) ?></div>
                                <div class="booking-location">📍 <?= htmlspecialchars($row['customer_location']) ?></div>
                                <div class="booking-meta mt-2">
                                    <p class="mb-1"><strong>Date:</strong> <?= $row['booking_date'] ?></p>
                                    <p class="mb-0"><strong>Time:</strong> <?= date("h:i A", strtotime($row['start_time'])) ?> – <?= date("h:i A", strtotime($row['end_time'])) ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="timer-text mb-2">Expires in: <span id="timer-<?= $row['booking_id'] ?>"><?= $seconds_left ?></span>s</div>
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-action btn-reject action-trigger" data-action="reject" data-id="<?= $row['booking_id'] ?>">Reject</button>
                                    <button type="button" class="btn btn-action btn-accept action-trigger" data-action="accept" data-id="<?= $row['booking_id'] ?>">Accept</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    var timeLeft<?= $row['booking_id'] ?> = <?= $seconds_left ?>;
                    var timerEl<?= $row['booking_id'] ?> = document.getElementById('timer-<?= $row['booking_id'] ?>');
                    setInterval(function() {
                        if (timeLeft<?= $row['booking_id'] ?> > 0) {
                            timeLeft<?= $row['booking_id'] ?>--;
                            timerEl<?= $row['booking_id'] ?>.innerText = timeLeft<?= $row['booking_id'] ?>;
                        } else {
                            timerEl<?= $row['booking_id'] ?>.innerText = "EXPIRED";
                            timerEl<?= $row['booking_id'] ?>.style.color = "#6c757d";
                        }
                    }, 1000);
                </script>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- SECTION 2: PAST BOOKINGS -->
        <?php foreach ($past_bookings as $row):

            // --- FIX: Calculate FULL Timestamp (Date + Time) ---
            // This ensures we compare the exact moment the booking ended, not just the time of day
            $full_end_timestamp = strtotime($row['booking_date'] . ' ' . $row['end_time']);
            $current_timestamp = time();

            // --- FIX: Check if time has passed (works for Yesterday, Today, or Last Week) ---
            $is_time_passed = ($current_timestamp > $full_end_timestamp);

            // Status Badge Logic
            $statusLabel = ucfirst($row['status']);
            $statusClass = 'status-booked';
            $actionButton = "";

            if ($row['status'] === 'completed') {
                $statusClass = 'status-completed';
            } elseif ($row['status'] === 'cancelled') {
                $statusClass = 'status-cancelled';
                $statusLabel = 'Cancelled';
            } elseif ($row['status'] === 'pending_verification') {
                $statusClass = 'status-pending-verification';
                $statusLabel = 'Pending Verification';
                $actionButton = '<span class="waiting-text"><i class="bi bi-hourglass-split"></i> Waiting for customer response...</span>';
            } elseif ($row['status'] === 'pending') {
                $statusLabel = "Expired";
                $statusClass = "status-expired";
            }

            // --- FIX: Updated Condition ---
            // Show button if Status is 'booked' AND the calculated end time has passed.
            // Removed the "is_same_day" check so old bookings also show the button.
            if ($row['status'] === 'booked' && $is_time_passed) {
                $actionButton = '<button type="button" class="btn btn-action btn-complete action-trigger" data-action="mark_completed" data-id="' . $row['booking_id'] . '">Mark Job Done</button>';
            }

            // Location Button Logic
            $locationButton = "";
            if (!empty($row['customer_latitude']) && !empty($row['customer_longitude'])) {
                $locationButton = '<button type="button" class="btn-view-loc" data-bs-toggle="modal" data-bs-target="#mapModal" data-lat="' . $row['customer_latitude'] . '" data-lng="' . $row['customer_longitude'] . '"><i class="bi bi-geo-alt-fill"></i> View Location</button>';
            }

            $extraMessage = ($row['status'] === 'cancelled') ? "<div class='text-danger small mt-2'><i class='bi bi-x-circle'></i> Booking was cancelled by customer</div>" : "";
        ?>
            <div class="card booking-card mb-4">
                <div class="booking-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <div class="booking-customer"><?= htmlspecialchars($row['customer_name']) ?></div>
                            <div class="booking-location">📍 <?= htmlspecialchars($row['customer_location']) ?></div>
                        </div>
                        <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </div>
                    <div class="booking-meta mt-3">
                        <p><strong>Date:</strong> <?= $row['booking_date'] ?></p>
                        <p><strong>Time:</strong> <?= date("h:i A", strtotime($row['start_time'])) ?> – <?= date("h:i A", strtotime($row['end_time'])) ?></p>

                        <!-- Dynamic Buttons -->
                        <?= $actionButton ?>
                        <?= $locationButton ?>

                        <?= $extraMessage ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- 1. HIDDEN FORM (Actions) -->
        <form id="actionForm" method="POST" style="display:none;">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="booking_id" id="formBookingId">
        </form>

        <!-- 2. CUSTOM POP UP MODAL (Confirm Actions) -->
        <div class="modal fade confirm-modal" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="modal-icon-container" id="modalIcon"><i class="bi bi-question-lg"></i></div>
                        <h4 class="fw-bold mb-2" id="modalTitle">Are you sure?</h4>
                        <p class="text-muted mb-4" id="modalMessage">Do you really want to perform this action?</p>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-cancel-custom" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-confirm" id="modalConfirmBtn">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. NEW: MAP MODAL -->
        <div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Customer Location</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- The Map Container -->
                        <div id="locationMap"></div>

                        <!-- Link to Google Maps for Navigation -->
                        <div class="text-center mt-2">
                            <a href="#" target="_blank" id="googleMapsLink" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-sign-turn-right"></i> Open in Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        // --- ACTION CONFIRMATION LOGIC ---
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const actionForm = document.getElementById('actionForm');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalIcon = document.getElementById('modalIcon');
        const modalContent = document.querySelector('.confirm-modal .modal-content');
        const confirmBtn = document.getElementById('modalConfirmBtn');

        document.querySelectorAll('.action-trigger').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                const bookingId = this.getAttribute('data-id');
                document.getElementById('formAction').value = action;
                document.getElementById('formBookingId').value = bookingId;

                modalContent.className = 'modal-content';

                if (action === 'accept') {
                    modalContent.classList.add('type-accept');
                    modalIcon.innerHTML = '<i class="bi bi-check-lg"></i>';
                    modalTitle.innerText = "Accept Booking?";
                    modalMessage.innerText = "You are about to accept this customer's request.";
                    confirmBtn.innerText = "Yes, Accept";
                } else if (action === 'reject') {
                    modalContent.classList.add('type-reject');
                    modalIcon.innerHTML = '<i class="bi bi-x-lg"></i>';
                    modalTitle.innerText = "Reject Booking?";
                    modalMessage.innerText = "This action cannot be undone. The customer will be notified.";
                    confirmBtn.innerText = "Yes, Reject";
                } else if (action === 'mark_completed') {
                    modalContent.classList.add('type-complete');
                    modalIcon.innerHTML = '<i class="bi bi-check-lg"></i>';
                    modalTitle.innerText = "Job Finished?";
                    modalMessage.innerText = "Marking this as complete will notify the customer for verification.";
                    confirmBtn.innerText = "Confirm Done";
                }
                confirmModal.show();
            });
        });

        document.getElementById('modalConfirmBtn').addEventListener('click', function() {
            actionForm.submit();
        });

        // --- MAP LOGIC (UPDATED) ---
        let map;
        const mapModal = document.getElementById('mapModal');

        // Initialize Map once when modal logic loads
        document.addEventListener("DOMContentLoaded", function() {
            // Default init (will be overwritten when button clicked)
            map = L.map('locationMap').setView([0, 0], 2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
        });

        // When View Location button is clicked
        document.querySelectorAll('.btn-view-loc').forEach(btn => {
            btn.addEventListener('click', function() {
                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));

                // Update Map View
                map.setView([lat, lng], 16);

                // Clear existing markers (if any) and add new one
                map.eachLayer(function(layer) {
                    if (!!layer.toGeoJSON) {
                        map.removeLayer(layer);
                    }
                });
                // Re-add tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                L.marker([lat, lng]).addTo(map)
                    .bindPopup("Customer Location").openPopup();

                // Update Google Maps Link
                // CHANGED: Used 'search' instead of 'dir' and 'query' instead of 'destination'
                // This forces Google Maps to pin the exact coordinates without trying to route immediately
                const gMapsLink = document.getElementById('googleMapsLink');
                gMapsLink.href = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            });
        });

        // Fix map rendering issue when modal opens (Leaflet needs visible container to size correctly)
        mapModal.addEventListener('shown.bs.modal', function() {
            setTimeout(function() {
                map.invalidateSize();
            }, 200);
        });
    </script>
</body>

</html>