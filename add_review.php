<?php
session_start();
require_once "connection.php";

// ---------------------------------------------------------
// 1. AUTHENTICATION CHECK
// ---------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

 $customer_id = $_SESSION['user_id'];

// ---------------------------------------------------------
// 2. LABOUR ID VALIDATION
// ---------------------------------------------------------
if (!isset($_GET['labour_id']) || !is_numeric($_GET['labour_id'])) {
    die("Invalid labour selection.");
}

 $labour_id = intval($_GET['labour_id']);

// ---------------------------------------------------------
// 3. FETCH LABOUR DETAILS
// ---------------------------------------------------------
 $stmt = $conn->prepare("SELECT name FROM labours WHERE labour_id = ?");
 $stmt->bind_param("i", $labour_id);
 $stmt->execute();
 $result = $stmt->get_result();
 $labour = $result->fetch_assoc();

if (!$labour) {
    die("Labour not found.");
}

// Variables to hold Modal State
 $modal_type = ""; // 'success' or 'error'
 $modal_message = "";
 $modal_title = "";
 $show_modal = false;

// ---------------------------------------------------------
// 4. HANDLE FORM SUBMISSION
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Basic Validation
    if ($rating < 1 || $rating > 5) {
        $modal_type = "error";
        $modal_title = "Invalid Rating";
        $modal_message = "Rating must be between 1 and 5 stars.";
        $show_modal = true;
    } else {
        // Check for Duplicate Review
        $check_review = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ?");
        $check_review->bind_param("i", $booking_id);
        $check_review->execute();
        $check_result = $check_review->get_result();

        if ($check_result->num_rows > 0) {
            // TRIGGER ERROR MODAL
            $modal_type = "error";
            $modal_title = "Review Already Exists";
            $modal_message = "You have already submitted a review for this booking.";
            $show_modal = true;
        } else {
            // Insert New Review
            $insert_stmt = $conn->prepare("INSERT INTO reviews (booking_id, rating, comment) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iis", $booking_id, $rating, $comment);

            if ($insert_stmt->execute()) {
                // TRIGGER SUCCESS MODAL
                $modal_type = "success";
                $modal_title = "Thank You!";
                $modal_message = "Your review has been added successfully.";
                $show_modal = true;
            } else {
                $modal_type = "error";
                $modal_title = "System Error";
                $modal_message = "Failed to add review. Please try again.";
                $show_modal = true;
            }
        }
    }
}

// ---------------------------------------------------------
// 5. FETCH CUSTOMER BOOKINGS
// ---------------------------------------------------------
 $booking_stmt = $conn->prepare("
    SELECT booking_id, booking_date 
    FROM bookings 
    WHERE customer_id = ? AND labour_id = ? 
    ORDER BY booking_date DESC
");
 $booking_stmt->bind_param("ii", $customer_id, $labour_id);
 $booking_stmt->execute();
 $bookings = $booking_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Review - <?= htmlspecialchars($labour['name']) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="icon" href="assets/images/favicon.png">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }

        .card-header-custom {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 25px 30px;
            text-align: center;
        }

        .card-body-custom {
            padding: 30px;
        }

        .star-rating {
            font-size: 2.2rem;
            color: #e4e5e9;
            cursor: pointer;
            display: flex;
            gap: 5px;
        }
        .star-rating span.active { color: #ffc107; }
        .star-rating span { transition: color 0.2s, transform 0.2s; }
        .star-rating span:hover { transform: scale(1.1); }

        .form-control, .form-select {
            padding: 12px;
            border-radius: 8px;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.25);
            border-color: #ffc107;
        }

        .btn-submit {
            background-color: #ffc107;
            color: #000;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        /* Modal Custom Styles */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
        }
        .modal-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
        }
        .icon-success { color: #198754; }
        .icon-error { color: #dc3545; }
        
        .btn-modal-confirm {
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="card card-custom">
        <div class="card-header-custom">
            <h3 class="mb-0 fw-bold">Write a Review</h3>
            <p class="text-muted mb-0 small mt-1">for <?= htmlspecialchars($labour['name']) ?></p>
        </div>

        <div class="card-body-custom">
            <?php if (!$show_modal): ?>
                <!-- Only show form if we aren't showing a success/error modal -->
                <?php if ($bookings->num_rows > 0): ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Select Booking</label>
                            <select name="booking_id" class="form-select shadow-sm" required>
                                <option value="" disabled selected>Choose a completed booking...</option>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <option value="<?= $booking['booking_id'] ?>">
                                        📅 <?= htmlspecialchars(date("d M Y", strtotime($booking['booking_date']))) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase">Rating</label>
                            <div class="star-rating" id="star-container">
                                <span class="star" data-value="1">&#9733;</span>
                                <span class="star" data-value="2">&#9733;</span>
                                <span class="star" data-value="3">&#9733;</span>
                                <span class="star" data-value="4">&#9733;</span>
                                <span class="star" data-value="5">&#9733;</span>
                            </div>
                            <input type="hidden" name="rating" id="rating-input" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase">Your Comment</label>
                            <textarea name="comment" class="form-control shadow-sm" rows="4" placeholder="Share your experience..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-submit w-100">Submit Review</button>
                    </form>
                <?php else: ?>
                    <!-- EMPTY STATE -->
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x" style="font-size: 3rem; color: #dee2e6;"></i>
                        <h5 class="mt-3">No Bookings Found</h5>
                        <p class="text-muted">You must have a booking to review.</p>
                        <a href="labour_profile.php?id=<?= $labour_id ?>" class="btn btn-outline-secondary rounded-pill">Back to Profile</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ---------------------------------------------------------
         PROFESSIONAL POPUP MODAL
    --------------------------------------------------------- -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <div class="modal-body">
                    <div id="modalIconContainer"></div>
                    <h4 id="modalTitle" class="fw-bold mb-3"></h4>
                    <p id="modalMessage" class="text-muted mb-4"></p>
                    <button type="button" class="btn btn-primary btn-modal-confirm" id="modalActionBtn">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ---------------------------------------------------------
         BOOTSTRAP JS (CRITICAL FOR MODALS)
    --------------------------------------------------------- -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // --- Star Rating Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('rating-input');
            let selectedRating = 0;

            if (stars.length > 0) {
                stars.forEach((star, index) => {
                    star.addEventListener('mouseover', () => highlightStars(index + 1));
                });
                document.getElementById('star-container').addEventListener('mouseleave', () => highlightStars(selectedRating));
                stars.forEach((star, index) => {
                    star.addEventListener('click', () => {
                        selectedRating = index + 1;
                        ratingInput.value = selectedRating;
                        highlightStars(selectedRating);
                        star.style.transform = 'scale(1.4)';
                        setTimeout(() => star.style.transform = 'scale(1.1)', 200);
                    });
                });
            }

            function highlightStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) star.classList.add('active');
                    else star.classList.remove('active');
                });
            }

            // --- Modal Trigger Logic ---
            const modalType = "<?= $modal_type ?>";
            const modalTitle = "<?= $modal_title ?>";
            const modalMessage = "<?= $modal_message ?>";
            const labourId = <?= $labour_id ?>;

            if (modalType && (modalType === 'success' || modalType === 'error')) {
                // Check if bootstrap is loaded
                if (typeof bootstrap !== 'undefined') {
                    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
                    
                    // Set Content
                    const iconContainer = document.getElementById('modalIconContainer');
                    document.getElementById('modalTitle').innerText = modalTitle;
                    document.getElementById('modalMessage').innerText = modalMessage;

                    if (modalType === 'success') {
                        iconContainer.innerHTML = '<i class="bi bi-check-circle-fill icon-success modal-icon"></i>';
                        document.getElementById('modalActionBtn').className = 'btn btn-success btn-modal-confirm';
                    } else {
                        iconContainer.innerHTML = '<i class="bi bi-exclamation-circle-fill icon-error modal-icon"></i>';
                        document.getElementById('modalActionBtn').className = 'btn btn-danger btn-modal-confirm';
                    }

                    // Handle Button Click -> Redirect
                    document.getElementById('modalActionBtn').onclick = function() {
                        window.location.href = `labour_profile.php?id=${labourId}`;
                    };

                    // Show Modal
                    statusModal.show();
                } else {
                    console.error("Bootstrap JS not loaded. Modal cannot appear.");
                    // Fallback just in case JS fails completely
                    alert(modalMessage + "\nRedirecting...");
                    window.location.href = `labour_profile.php?id=${labourId}`;
                }
            }
        });
    </script>
</body>
</html>