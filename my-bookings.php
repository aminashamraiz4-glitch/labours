<?php
session_start();
include 'connection.php';

// HARD STOP: must be logged in customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit;
}

 $customer_id = $_SESSION['user_id'];

// ============================================================
// HANDLE CANCELLATION & VERIFICATION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle Cancellation
    if (isset($_POST['cancel_booking_id'])) {
        $cancel_id = intval($_POST['cancel_booking_id']);
        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $cancel_id, $customer_id);
        if ($stmt->execute()) {
            header("Location: my-bookings.php");
            exit;
        }
    }

    // 2. Handle VERIFICATION (Customer marking job as done)
    if (isset($_POST['verify_booking_id'])) {
        $verify_id = intval($_POST['verify_booking_id']);
        // Security: Ensure status is actually pending_verification first
        $stmt = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE booking_id = ? AND customer_id = ? AND status = 'pending_verification'");
        $stmt->bind_param("ii", $verify_id, $customer_id);
        if ($stmt->execute()) {
            header("Location: my-bookings.php");
            exit;
        }
    }
}

// Fetch bookings with labour details
 $query = "
    SELECT 
        b.booking_id,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.status,
        b.created_at,   
        l.name AS labour_name,
        l.skill,
        l.phone,
        l.image
    FROM bookings b
    INNER JOIN labours l ON b.labour_id = l.labour_id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
";

 $stmt = $conn->prepare($query);
 $stmt->bind_param("i", $customer_id);
 $stmt->execute();
 $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Dashboard</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* --- COLOR PALETTE --- */
        :root {
            --mustard: #E1AD01;
            --mustard-hover: #D4A600;
            --silver-light: #F8F9FA;
            --silver-border: #E9ECEF;
            --silver-text: #ADB5BD;
            --charcoal: #212529;
            --charcoal-soft: #495057;
            --white: #FFFFFF;
            --whatsapp-green: #25D366;
            --danger: #FA5252;
            --danger-hover: #E03131;
            --info-blue: #1C7ED6;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--silver-light);
            background-image: radial-gradient(var(--silver-border) 1.5px, transparent 1.5px);
            background-size: 24px 24px;
            color: var(--charcoal);
            min-height: 100vh;
            padding-bottom: 100px;
        }

        /* --- ANIMATIONS --- */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-entry {
            animation: slideUp 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--charcoal) !important;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
        }

        .back-btn {
            background: var(--white);
            border: 2px solid var(--silver-border);
            color: var(--charcoal);
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .back-btn:hover {
            border-color: var(--mustard);
            color: var(--mustard);
            transform: translateX(-3px);
        }

        /* --- BOOKING CARD --- */
        .booking-card {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid var(--silver-border);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(225, 173, 1, 0.15);
            border-color: rgba(225, 173, 1, 0.3);
        }

        .card-decoration {
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            background: var(--mustard);
            opacity: 0.05;
            border-radius: 50%;
            z-index: 0;
            transition: transform 0.5s;
        }

        .booking-card:hover .card-decoration {
            transform: scale(1.5);
        }

        .avatar-container {
            position: relative;
            z-index: 1;
        }

        .labour-avatar {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }

        /* Status Pill */
        .status-pill {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 50px;
            letter-spacing: 0.5px;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .pill-booked { background: #FFF9DB; color: #F59F00; }
        .pill-completed { background: #EBFBEE; color: #20C997; }
        .pill-cancelled { background: #FFF5F5; color: var(--danger); }
        .pill-pending { background: #E7F5FF; color: #1C7ED6; }
        
        /* New Status: Pending Verification (Labour marked done, Customer needs to check) */
        .pill-pending-verification { 
            background: #E7F5FF; 
            color: #1C7ED6; 
            border: 1px solid #A5D8FF; 
        }

        /* Info Icons */
        .info-icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background-color: var(--silver-light);
            color: var(--charcoal-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .booking-card:hover .info-icon-circle {
            background-color: var(--mustard);
            color: var(--white);
        }

        .info-text {
            font-weight: 500;
            color: var(--charcoal-soft);
            font-size: 0.95rem;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 10px;
            z-index: 1;
        }

        .btn-wa {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 0.7rem 1.2rem;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-wa:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.4);
            color: white;
        }

        .btn-cancel {
            background: white;
            color: var(--danger);
            border: 2px solid var(--silver-border);
            border-radius: 16px;
            padding: 0.7rem 1.2rem;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #FFF5F5;
            border-color: var(--danger);
            color: var(--danger-hover);
        }

        /* NEW: Verify Button Style */
        .btn-verify {
            background: linear-gradient(135deg, var(--mustard), #c49400);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 0.7rem 1.2rem;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 4px 15px rgba(225, 173, 1, 0.4);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-verify:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(225, 173, 1, 0.5);
            color: white;
        }

        /* Typography */
        .labour-name {
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--charcoal);
            margin-bottom: 2px;
        }

        .labour-skill {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--mustard);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            display: block;
        }

        /* --- EMPTY STATE --- */
        .empty-box {
            background: var(--white);
            border-radius: 30px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--silver-border);
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background: var(--silver-light);
            color: var(--silver-text);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        /* --- CUSTOM MODAL STYLES --- */
        .custom-modal .modal-content {
            border: none;
            border-radius: 24px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
            padding: 2rem;
            text-align: center;
        }

        /* Cancel Modal Icon */
        .cancel-modal-icon {
            width: 70px;
            height: 70px;
            background-color: #FFF5F5;
            color: var(--danger);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem auto;
            animation: pulse 2s infinite;
        }

        /* Verify Modal Icon */
        .verify-modal-icon {
            width: 70px;
            height: 70px;
            background-color: #FFF9DB; /* Light Mustard */
            color: var(--mustard);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem auto;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(250, 82, 82, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(250, 82, 82, 0); }
            100% { box-shadow: 0 0 0 0 rgba(250, 82, 82, 0); }
        }

        .btn-modal-confirm {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .btn-modal-confirm:hover { background-color: var(--danger-hover); transform: scale(1.05); color: white; }

        /* Confirm Button for Verify (Mustard) */
        .btn-modal-verify {
            background-color: var(--mustard);
            color: var(--charcoal);
            border: none;
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .btn-modal-verify:hover { background-color: #c49400; transform: scale(1.05); color: white; }

        .btn-modal-close {
            background-color: #F1F3F5;
            color: var(--charcoal);
            border: none;
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .btn-modal-close:hover { background-color: #E9ECEF; color: var(--charcoal); }

        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .booking-card { padding: 1.25rem; border-radius: 20px; }
            .labour-avatar { width: 60px; height: 60px; border-radius: 16px; }
            .action-group { width: 100%; margin-top: 1rem; }
            .btn-wa, .btn-cancel, .btn-verify { flex: 1; justify-content: center; }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="d-flex align-items-center gap-3">
                <a href="home.php" class="back-btn">
                    <i class="bi bi-arrow-left fs-5"></i>
                </a>
                <div>
                    <span class="d-block text-muted small fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">CUSTOMER PORTAL</span>
                    <span class="navbar-brand d-block p-0 m-0 lh-1">My Bookings</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <?php if ($result->num_rows === 0): ?>
                    <!-- Empty State -->
                    <div class="empty-box animate-entry">
                        <div class="empty-icon">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <h3 class="fw-bold mb-3">No Bookings Yet</h3>
                        <p class="text-muted mb-4">Your dashboard is empty. Let's find some amazing talent!</p>
                        <a href="index.php" class="btn btn-dark rounded-pill px-4 py-2 fw-bold">
                            Browse Labours <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>

                <?php else: ?>

                    <?php
                    $delay = 0;
                    while ($row = $result->fetch_assoc()):
                        
                        // Status Logic & Pill Class
                        $status_text = ucfirst($row['status']);
                        $pill_class = 'pill-booked'; 
                        $actionButton = ''; // Holds the dynamic button HTML

                        // 30 MINUTE CANCELLATION RULE
                        $created_time = strtotime($row['created_at']);
                        $time_diff = time() - $created_time;
                        $can_cancel = ($time_diff < 1800); 

                        // WhatsApp Logic
                        $cleanPhone = preg_replace('/[^0-9]/', '', $row['phone']);
                        if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
                            if (substr($cleanPhone, 0, 1) === '0') {
                                $cleanPhone = '92' . substr($cleanPhone, 1);
                            }
                        }
                        $waMessage = urlencode("Hi " . $row['labour_name'] . "! I have a booking with you on " . date("M j", strtotime($row['booking_date'])) . ".");
                        $waLink = "https://wa.me/" . $cleanPhone . "?text=" . $waMessage;

                        // Avatar Fallback
                        $fallbackAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($row['labour_name']) . '&background=E1AD01&color=fff&size=150';
                        $imagePath = (!empty($row['image'])) ? 'assets/images/profilePictures/' . $row['image'] : $fallbackAvatar;

                        // --- DETERMINE BUTTON DISPLAY BASED ON STATUS ---
                        if ($row['status'] === 'pending_verification') {
                            // Labour marked done, Customer needs to Verify
                            $pill_class = 'pill-pending-verification';
                            $status_text = 'Awaiting Verification';
                            $actionButton = '
                                <button type="button" class="btn-verify trigger-verify-modal" data-id="'.$row['booking_id'].'">
                                    <i class="bi bi-shield-check"></i> Verify Job Done
                                </button>
                            ';
                        } elseif ($row['status'] === 'completed') {
                            // Job is fully done
                            $pill_class = 'pill-completed';
                            $status_text = 'Completed';
                            // No button needed, maybe show a "Rate Us" later, but for now empty
                        } elseif ($row['status'] === 'cancelled') {
                            $pill_class = 'pill-cancelled';
                            $status_text = 'Cancelled';
                        } elseif ($row['status'] === 'pending') {
                            $pill_class = 'pill-pending';
                            // Initial request state
                        }

                        // Cancellation Logic (Only if not verified or completed)
                        if (($row['status'] === 'pending' || $row['status'] === 'booked') && $can_cancel) {
                            $actionButton = '
                                <button type="button" class="btn-cancel trigger-cancel-modal" data-id="'.$row['booking_id'].'">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            ';
                        }

                        $delay += 0.1;
                    ?>

                        <!-- Card -->
                        <div class="booking-card animate-entry" style="animation-delay: <?= $delay ?>s">
                            <div class="card-decoration"></div>

                            <div class="row align-items-center">
                                <!-- Left: Avatar -->
                                <div class="col-auto avatar-container">
                                    <img src="<?= $imagePath ?>" class="labour-avatar" alt="Labour">
                                </div>

                                <!-- Middle: Details -->
                                <div class="col">
                                    <h5 class="labour-name"><?= htmlspecialchars($row['labour_name']) ?></h5>
                                    <span class="labour-skill"><?= htmlspecialchars($row['skill']) ?></span>

                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="info-icon-circle">
                                                <i class="bi bi-calendar3"></i>
                                            </div>
                                            <span class="info-text"><?= date("M j, Y", strtotime($row['booking_date'])) ?></span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <div class="info-icon-circle">
                                                <i class="bi bi-clock"></i>
                                            </div>
                                            <span class="info-text"><?= date("h:i A", strtotime($row['start_time'])) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Status & Actions -->
                                <div class="col-auto text-end d-none d-md-block">
                                    <div class="mb-2">
                                        <span class="status-pill <?= $pill_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </div>

                                    <!-- Action Group -->
                                    <div class="action-group">
                                        <!-- Cancel Button -->
                                        <!-- (Injected via $actionButton logic above) -->
                                        <?php if ($row['status'] === 'pending' || $row['status'] === 'booked'): ?>
                                            <?= $actionButton ?>
                                        <?php endif; ?>

                                        <!-- Verify Button -->
                                        <?php if ($row['status'] === 'pending_verification'): ?>
                                            <?= $actionButton ?>
                                        <?php endif; ?>

                                        <!-- WhatsApp Button -->
                                        <?php if ($row['status'] !== 'completed' && $row['status'] !== 'cancelled'): ?>
                                            <a href="<?= $waLink ?>" target="_blank" class="btn-wa">
                                                <i class="bi bi-whatsapp"></i> Chat
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Action Row -->
                            <div class="row d-md-none mt-3 pt-3 border-top">
                                <div class="col-6 d-flex align-items-center">
                                    <span class="status-pill <?= $pill_class ?>">
                                        <?= $status_text ?>
                                    </span>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="action-group flex-column">
                                         <!-- Mobile Actions -->
                                        <?php if ($row['status'] === 'pending_verification' || ($row['status'] === 'pending' || $row['status'] === 'booked')): ?>
                                            <?= $actionButton ?>
                                        <?php endif; ?>

                                        <!-- Mobile WhatsApp -->
                                        <?php if ($row['status'] !== 'completed' && $row['status'] !== 'cancelled'): ?>
                                            <a href="<?= $waLink ?>" target="_blank" class="btn-wa w-100">
                                                <i class="bi bi-whatsapp"></i> Chat Now
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php endif; ?>


            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- HIDDEN FORMS & MODALS -->
    <!-- ============================================================ -->
    
    <!-- 1. HIDDEN CANCEL FORM -->
    <form id="cancelForm" method="POST" style="display:none;">
        <input type="hidden" name="cancel_booking_id" id="cancelIdInput">
    </form>

    <!-- 2. HIDDEN VERIFY FORM -->
    <form id="verifyForm" method="POST" style="display:none;">
        <input type="hidden" name="verify_booking_id" id="verifyIdInput">
    </form>

    <!-- 3. CANCEL MODAL -->
    <div class="modal fade custom-modal" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="cancel-modal-icon">
                    <i class="bi bi-x-lg"></i>
                </div>
                
                <h4 class="fw-bold mb-2">Cancel Booking?</h4>
                <p class="text-muted mb-4 px-3">
                    Are you sure you want to cancel this booking? This action cannot be undone.
                </p>

                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn-modal-close" data-bs-dismiss="modal">No, Keep It</button>
                    <button type="button" class="btn-modal-confirm" id="confirmCancelBtn">Yes, Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. VERIFY MODAL -->
    <div class="modal fade custom-modal" id="verifyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="verify-modal-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                
                <h4 class="fw-bold mb-2">Confirm Job Done?</h4>
                <p class="text-muted mb-4 px-3">
                    Has the labour completed the job to your satisfaction? Confirming will finalize the booking.
                </p>

                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn-modal-close" data-bs-dismiss="modal">Not Yet</button>
                    <button type="button" class="btn-modal-verify" id="confirmVerifyBtn">Yes, Verify</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Script -->
    <script>
        // --- CANCEL MODAL LOGIC ---
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
        const cancelForm = document.getElementById('cancelForm');
        const cancelIdInput = document.getElementById('cancelIdInput');
        const confirmCancelBtn = document.getElementById('confirmCancelBtn');

        document.querySelectorAll('.trigger-cancel-modal').forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-id');
                cancelIdInput.value = bookingId;
                cancelModal.show();
            });
        });

        confirmCancelBtn.addEventListener('click', function() {
            cancelForm.submit();
        });

        // --- VERIFY MODAL LOGIC ---
        const verifyModal = new bootstrap.Modal(document.getElementById('verifyModal'));
        const verifyForm = document.getElementById('verifyForm');
        const verifyIdInput = document.getElementById('verifyIdInput');
        const confirmVerifyBtn = document.getElementById('confirmVerifyBtn');

        document.querySelectorAll('.trigger-verify-modal').forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-id');
                verifyIdInput.value = bookingId;
                verifyModal.show();
            });
        });

        confirmVerifyBtn.addEventListener('click', function() {
            verifyForm.submit();
        });
    </script>
</body>

</html>