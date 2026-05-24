<?php
session_start();

if (isset($_SESSION['login_success'])): ?>

    <div class="welcome-popup-overlay" id="welcomePopup">

        <div class="welcome-popup">

            <div class="popup-icon">
                👋
            </div>

            <h2>
                Welcome Back,
                <span>
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
            </h2>

            <p>
                Successfully logged in as
                <?= htmlspecialchars($_SESSION['user_type']) ?>.
            </p>

            <button type="button" id="closePopupBtn">
    Continue
</button>

        </div>

    </div>

<?php
    unset($_SESSION['login_success']);
endif;


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once "connection.php";

// Check login
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// --- HANDLE STATUS TOGGLE (Only for Labours) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status']) && $user_type == 'labour') {
    // First fetch current status safely
    $check_sql = "SELECT live_status FROM labours WHERE labour_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $res = $check_stmt->get_result()->fetch_assoc();

    $current_status = $res['live_status'] ?? 'busy';
    $new_status = ($current_status == 'available') ? 'busy' : 'available';

    // Update
    $update_sql = "UPDATE labours SET live_status = ? WHERE labour_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $user_id);
    $update_stmt->execute();

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="icon" href="assets/images/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/logout.css">


    <style>
        :root {
            --mustard: #D4A017;
            /* Rich Mustard */
            --mustard-dark: #B8860B;
            /* Darker Mustard for hover */
            --mustard-light: #FFF8E1;
            /* Very light mustard for backgrounds */
            --black: #121212;
            /* Deep Black */
            --black-light: #1E1E1E;
            /* Lighter Black for gradients */
            --silver: #A8A8A8;
            /* Medium Silver */
            --silver-light: #E8E8E8;
            /* Light Silver for borders/bg */
            --silver-bg: #F4F4F4;
            /* Off-white Silver for body */
            --white: #FFFFFF;
            --text-main: #1A1A1A;
            --text-muted: #6B6B6B;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.12);
            --radius: 16px;
        }

        * {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: var(--silver-bg);
            color: var(--text-main);
            padding-bottom: 40px;
        }

        .profile-wrapper {
            max-width: 900px;
            margin: auto;
            padding-top: 40px;
        }

        /* Top Navigation Bar */
        .top-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.25s ease;
            border: 1.5px solid var(--silver-light);
            background: var(--white);
            color: var(--black);
            box-shadow: var(--shadow-sm);
        }

        .action-btn:hover {
            border-color: var(--silver);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--black);
        }

        .action-btn.logout {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fff5f5;
        }

        .action-btn.logout:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        /* Cards */
        .profile-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            border: 1px solid var(--silver-light);
            transition: box-shadow 0.3s ease;
        }

        .profile-card:hover {
            box-shadow: var(--shadow-lg);
        }

        /* Profile Header */
        .profile-header-card {
            text-align: center;
            padding-top: 50px;
            padding-bottom: 35px;
            position: relative;
            overflow: hidden;
        }

        .profile-header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 110px;
            background: linear-gradient(135deg, var(--black) 0%, var(--black-light) 100%);
            border-bottom: 4px solid var(--mustard);
        }

        .profile-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid var(--mustard);
            object-fit: cover;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            background-color: var(--white);
        }

        .profile-name {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--black);
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
            letter-spacing: -0.5px;
        }

        /* Live Status Toggle Styles */
        .live-status-container {
            margin-top: 20px;
            position: relative;
            z-index: 2;
        }

        .btn-status-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-status-toggle.status-available {
            background: var(--mustard);
            color: var(--black);
        }

        .btn-status-toggle.status-available:hover {
            background: var(--mustard-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(212, 160, 23, 0.4);
        }

        .btn-status-toggle.status-busy {
            background: #e5e5e5;
            color: #666;
        }

        .btn-status-toggle.status-busy:hover {
            background: #d4d4d4;
            transform: translateY(-2px);
            color: #000;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-available .status-dot {
            background-color: #000;
        }

        .status-busy .status-dot {
            background-color: #999;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .info-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--silver-light);
            color: var(--mustard-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            border: 1px solid #ddd;
        }

        .info-content h6 {
            margin-bottom: 2px;
            font-size: 0.75rem;
            color: var(--silver);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
        }

        .info-content p {
            margin: 0;
            font-weight: 600;
            color: var(--black);
            font-size: 0.95rem;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: var(--black);
            padding: 24px;
            border-radius: 14px;
            text-align: center;
            border: 1px solid var(--black-light);
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--mustard);
        }

        .stat-card h3 {
            font-weight: 800;
            color: var(--mustard);
            margin-bottom: 5px;
            font-size: 2rem;
        }

        .stat-card small {
            color: var(--silver-light);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        /* Section Titles */
        .section-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--black);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title i {
            color: var(--mustard-dark);
            font-size: 1.3rem;
        }

        /* Table Modernization */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--silver-light);
        }

        .modern-table thead th {
            background: var(--black);
            color: var(--mustard);
            border-bottom: 2px solid var(--mustard-dark);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            padding: 14px 16px;
        }

        .modern-table tbody td {
            padding: 16px;
            border-bottom: 1px solid var(--silver-light);
            vertical-align: middle;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-main);
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        .modern-table tbody tr:hover {
            background: var(--mustard-light);
        }

        .welcome-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        .welcome-popup {
            background: white;
            width: 90%;
            max-width: 420px;
            padding: 40px 30px;
            border-radius: 25px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            animation: popupScale 0.4s ease;
        }

        .popup-icon {
            width: 90px;
            height: 90px;
            margin: auto;
            background: #ffc107;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            margin-bottom: 20px;
        }

        .welcome-popup h2 {
            font-size: 30px;
            margin-bottom: 10px;
            color: #222;
        }

        .welcome-popup h2 span {
            color: #f59e0b;
        }

        .welcome-popup p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .welcome-popup button {
            border: none;
            background: #ffc107;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s;
        }

        .welcome-popup button:hover {
            transform: scale(1.05);
            background: #e0a800;
        }

        @keyframes popupScale {
            from {
                transform: scale(0.7);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <div class="profile-wrapper">

        <!-- TOP ACTION BUTTONS -->
        <div class="top-actions">
            <a href="home.php" class="action-btn">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
            <a href="#" class="action-btn logout" onclick="confirmLogout()">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>

        <?php
        //LABOUR PROFILE
        if ($user_type == 'labour') {

            $sql = "SELECT * FROM labours WHERE labour_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $labour = $result->fetch_assoc();

            // stats query
            $statsSql = "SELECT COUNT(*) AS total_bookings, SUM(LOWER(status) = 'completed') AS completed_bookings FROM bookings WHERE labour_id = ?";
            $statsStmt = $conn->prepare($statsSql);
            $statsStmt->bind_param("i", $user_id);
            $statsStmt->execute();
            $stats = $statsStmt->get_result()->fetch_assoc();

            $totalBookings = $stats['total_bookings'] ?? 0;
            $completedBookings = $stats['completed_bookings'] ?? 0;

            if ($labour) {
                // --- IMAGE LOGIC ---
                // 1. Define the Fallback Avatar
                $fallbackAvatar = "https://ui-avatars.com/api/?name=" . urlencode($labour['name']) . "&background=121212&color=D4A017&size=150&bold=true";

                // 2. Try to use uploaded image (Relative path ../uploads/ because file is in labours/ folder)
                if (!empty($labour['image'])) {
                    $imagePath = "assets/images/profilePictures/" . $labour['image'];
                } else {
                    $imagePath = $fallbackAvatar;
                }

                // Determine Live Status Button Style
                $live_status = $labour['live_status'] ?? 'busy';
                if ($live_status == 'available') {
                    $btn_class = "status-available";
                    $btn_text = "Currently Available";
                    $btn_icon = "bi-stop-circle";
                } else {
                    $btn_class = "status-busy";
                    $btn_text = "Currently Busy / Offline";
                    $btn_icon = "bi-play-circle";
                }

                echo '
                <div class="profile-card profile-header-card">
                    <!-- onerror added: if image fails to load, switch to fallbackAvatar -->
                    <img src="' . $imagePath . '" 
                         onerror="this.src=\'' . $fallbackAvatar . '\'" 
                         class="profile-img" 
                         alt="Profile Picture">
                         
                    <h2 class="profile-name">' . $labour['name'] . '</h2>
                    
                    <!-- LIVE STATUS TOGGLE BUTTON -->
                    <div class="live-status-container">
                        <form method="POST" style="display:inline;">
                            <button type="submit" name="toggle_status" class="btn-status-toggle ' . $btn_class . '">
                                <span class="status-dot"></span>
                                <i class="bi ' . $btn_icon . '"></i> ' . $btn_text . '
                            </button>
                        </form>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-envelope-fill"></i></div>
                            <div class="info-content">
                                <h6>Email</h6>
                                <p>' . $labour['email'] . '</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-telephone-fill"></i></div>
                            <div class="info-content">
                                <h6>Phone</h6>
                                <p>' . $labour['phone'] . '</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-tools"></i></div>
                            <div class="info-content">
                                <h6>Skill</h6>
                                <p>' . $labour['skill'] . '</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div class="info-content">
                                <h6>Location</h6>
                                <p>' . $labour['location'] . '</p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-container">
                        <div class="stat-card">
                            <h3>' . (int)$totalBookings . '</h3>
                            <small>Total Bookings</small>
                        </div>
                        <div class="stat-card">
                            <h3>' . (int)$completedBookings . '</h3>
                            <small>Completed Jobs</small>
                        </div>
                    </div>
                </div>';
            } else {
                echo '<div class="alert alert-warning">Labour record not found.</div>';
            }
        }

        // CUSTOMER PROFILE
        elseif ($user_type == 'customer') {

            $sql = "SELECT * FROM customers WHERE customer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();

            if ($customer) {
                // Fallback Avatar
                $customerImg = "https://ui-avatars.com/api/?name=" . urlencode($customer['name']) . "&background=121212&color=D4A017&size=150&bold=true";

                echo '
                <div class="profile-card profile-header-card">
                    <img src="' . $customerImg . '" class="profile-img" alt="Profile Picture">
                    <h2 class="profile-name">' . $customer['name'] . '</h2>
                </div>

                <div class="profile-card">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-envelope-fill"></i></div>
                            <div class="info-content">
                                <h6>Email</h6>
                                <p>' . $customer['email'] . '</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div class="info-content">
                                <h6>Location</h6>
                                <p>' . $customer['location'] . '</p>
                            </div>
                        </div>
                    </div>
                </div>';

                // Fetch bookings
                $sql = "SELECT b.*, l.name AS labour_name, l.skill AS labour_skill 
                FROM bookings b 
                JOIN labours l ON b.labour_id = l.labour_id 
                WHERE b.customer_id = ?
                ORDER BY b.booking_date DESC";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                echo '<div class="profile-card">
                        <div class="section-title">
                            <i class="bi bi-calendar-check"></i> Your Bookings
                        </div>';

                if ($result->num_rows > 0) {
                    echo '<div class="table-responsive">
                          <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Labour</th>
                                    <th>Skill</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>';

                    while ($booking = $result->fetch_assoc()) {
                        // Status badge logic
                        $status = strtolower($booking['status']);
                        $bg_class = "bg-secondary";
                        if ($status == 'completed') $bg_class = "bg-dark";
                        if ($status == 'pending') $bg_class = "text-dark";
                        if ($status == 'cancelled') $bg_class = "border border-danger text-danger bg-transparent";

                        $custom_style = "";
                        if ($status == 'pending') {
                            $custom_style = "style='background-color: #D4A017; color: #000; font-weight:700;'";
                        }
                        if ($status == 'completed') {
                            $custom_style = "style='background-color: #1A1A1A; color: #fff;'";
                        }

                        echo '
                        <tr>
                            <td class="fw-medium">' . $booking['labour_name'] . '</td>
                            <td>' . $booking['labour_skill'] . '</td>
                            <td>' . date('M d, Y', strtotime($booking['booking_date'])) . '</td>
                            <td>' . date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time'])) . '</td>
                            <td><span class="badge ' . $bg_class . ' px-3 py-2 rounded-pill" ' . $custom_style . '>' . ucfirst($status) . '</span></td>
                        </tr>';
                    }

                    echo '</tbody></table></div>';
                } else {
                    echo '<div class="text-center py-4" style="color: var(--silver);">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No bookings found yet.
                          </div>';
                }
                echo '</div>';
            }
        }
        ?>

        <!-- Logout Popup -->
        <div id="logoutPopup" onclick="handleOverlayClick(event)">
            <div class="popup-card" onclick="event.stopPropagation()">

                <!-- Logout Icon -->
                <div class="icon-wrapper">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                </div>

                <!-- Divider dots -->
                <div class="divider-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <!-- Text -->
                <h5>Are you sure you want to logout?</h5>
                <p class="sub-text">You will need to login again to access your account</p>

                <!-- Buttons -->
                <div class="btn-group">
                    <button class="btn-cancel" onclick="closePopup()">Cancel</button>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>

            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {

                const closeBtn = document.getElementById("closePopupBtn");

                if (closeBtn) {
                    closeBtn.addEventListener("click", function() {

                        const popup = document.getElementById("welcomePopup");

                        if (popup) {
                            popup.style.display = "none";
                        }

                    });
                }

            });
        </script>
        </script>
        <script src="assets/logout.js"></script>

</body>

</html>