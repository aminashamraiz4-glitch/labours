<?php
session_start();
require_once "connection.php";

// FIX: Check SESSION for page access, not POST
 $user_type = $_SESSION['user_type'] ?? '';
 $user_id   = $_SESSION['user_id'] ?? 0;
 $table     = '';

if ($user_type == 'labour') {
    $table = 'labours';
} elseif ($user_type == 'customer') {
    $table = 'customers';
} else {
    header("Location: login.php");
    exit();
}

 $user = [];
if ($user_id > 0) {
    $id_field = ($user_type == 'labour') ? 'labour_id' : 'customer_id';
    $stmt = $conn->prepare("SELECT * FROM $table WHERE $id_field = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}

// Check for flash message from update_profile.php
 $flash_msg = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
if ($flash_msg) {
    unset($_SESSION['flash_message']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Dashboard</title>
    <link rel="icon" href="assets/images/favicon.png">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --mustard: #E1AD01;
            --mustard-dark: #C49500;
            --charcoal: #212529;
            --silver-border: #DEE2E6;
            --bg-light: #F8F9FA;
            --text-muted: #6C757D;
        }

        body {
            background-color: #FFFFFF;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--charcoal);
            min-height: 100vh;
        }

        /* --- Navbar --- */
        .navbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--silver-border);
            padding: 1rem 0;
        }

        .nav-link-back {
            color: var(--charcoal);
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link-back:hover {
            color: var(--mustard);
        }

        /* --- Main Card --- */
        .profile-card {
            max-width: 800px;
            margin: 40px auto;
            border: none;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            padding: 3rem;
            background: #fff;
        }

        h2.page-title {
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 2rem;
        }

        /* --- Form Styling --- */
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--charcoal);
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border: 1px solid var(--silver-border);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            background-color: var(--bg-light);
            transition: all 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--mustard);
            box-shadow: 0 0 0 4px rgba(225, 173, 1, 0.1);
            background-color: #fff;
        }

        /* --- Avatar Upload --- */
        .avatar-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem auto;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            group: hover;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .avatar-wrapper:hover .avatar-overlay {
            opacity: 1;
        }

        .avatar-wrapper:hover .avatar-img {
            transform: scale(1.1);
        }

        #profile_pic {
            display: none;
        }

        /* --- Section Dividers --- */
        .section-header {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 700;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--silver-border);
        }

        /* --- Button --- */
        .btn-save {
            background: var(--mustard);
            color: var(--charcoal);
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-save:hover {
            background: var(--mustard-dark);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(225, 173, 1, 0.25);
        }

        /* --- Popup Styles --- */
        .status-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .status-overlay.show {
            display: flex;
            opacity: 1;
        }

        .status-card {
            background: #fff;
            width: 90%;
            max-width: 400px;
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            transform: scale(0.8);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .status-overlay.show .status-card {
            transform: scale(1);
        }

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
        }

        .status-card.success::before {
            background: #10B981;
        }

        .status-card.error::before {
            background: #EF4444;
        }

        .status-icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .status-card.success .status-icon-circle {
            background: #10B981;
        }

        .status-card.error .status-icon-circle {
            background: #EF4444;
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #212529;
        }

        .status-msg {
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 1rem;
            line-height: 1.5;
        }

        .btn-status-close {
            background: #212529;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
        }

        .btn-status-close:hover {
            background: #000;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="profile.php" class="nav-link-back">
                <i class="bi bi-arrow-left-circle-fill fs-4"></i>
                <span>Back to Profile</span>
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="profile-card">

            <?php if (!empty($user)): ?>

                <h2 class="page-title text-center">Edit Profile</h2>

                <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">

                    <!-- Avatar Upload -->
                    <div class="avatar-wrapper" onclick="document.getElementById('profile_pic').click()">
                        <?php
                        // FIX: Updated path to profilePictures (plural)
                        $fallbackAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=E1AD01&color=fff&size=200';
                        $realImage = (!empty($user['image'])) ? 'assets/images/profilePictures/' . $user['image'] : $fallbackAvatar;
                        ?>
                        <img src="<?= $realImage ?>" alt="Profile" class="avatar-img" id="avatarPreview" onerror="this.src='<?= $fallbackAvatar ?>';">
                        <div class="avatar-overlay"><i class="bi bi-camera fs-4"></i></div>
                    </div>
                    <div class="text-center mb-4"><small class="text-muted">Click image to change</small></div>

                    <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept="image/*" onchange="previewImage(event)">

                    <!-- Personal Information -->
                    <div class="section-header">Personal Information</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <?php if ($user_type == 'labour'): ?>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="skill" class="form-label">Skill</label>
                                <select class="form-select" name="skill" id="skill">
                                    <?php
                                    $skills = ['Plumber', 'Electrician', 'Carpenter', 'Cleaner', 'Painter', 'Mover', 'Other'];
                                    foreach ($skills as $skill) {
                                        $selected = (isset($user['skill']) && $user['skill'] == $skill) ? 'selected' : '';
                                        echo "<option value='$skill' $selected>$skill</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="location" class="form-label">Location / City</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($user['location']); ?>">
                            </div>
                        <?php else: ?>
                            <div class="col-12">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($user['location']); ?>">
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Security -->
                    <div class="section-header">Security</div>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-save">Save Changes</button>
                    </div>
                </form>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-exclamation-circle text-danger fs-1"></i>
                    <h3 class="mt-3">User not found</h3>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Popup HTML -->
    <div class="status-overlay" id="statusPopup">
        <div class="status-card" id="statusCard">
            <div class="status-icon-circle" id="statusIcon"><i class="bi bi-check-lg"></i></div>
            <h3 class="status-title" id="statusTitle">Success!</h3>
            <p class="status-msg" id="statusMessage">Operation completed successfully.</p>
            <button class="btn-status-close" onclick="closePopup()">Okay</button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview Image
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('avatarPreview').src = reader.result;
            };
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }

        // Handle Popup
        const flashMessage = <?php echo json_encode($flash_msg); ?>;
        document.addEventListener("DOMContentLoaded", function() {
            if (flashMessage) {
                showPopup(flashMessage.type, flashMessage.msg);
            }
        });

        function showPopup(type, message) {
            const popup = document.getElementById('statusPopup');
            const card = document.getElementById('statusCard');
            const icon = document.getElementById('statusIcon');
            const title = document.getElementById('statusTitle');
            const msg = document.getElementById('statusMessage');
            card.className = 'status-card';
            if (type === 'success') {
                card.classList.add('success');
                icon.innerHTML = '<i class="bi bi-check-lg"></i>';
                title.innerText = 'Success!';
            } else {
                card.classList.add('error');
                icon.innerHTML = '<i class="bi bi-x-lg"></i>';
                title.innerText = 'Error!';
            }
            msg.innerText = message;
            popup.classList.add('show');
        }

        function closePopup() {
            document.getElementById('statusPopup').classList.remove('show');
        }
    </script>
</body>

</html>