<?php
session_start();
require_once "connection.php"; 

// 1. Check for flash message from previous submission
 $flash_msg = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
if ($flash_msg) {
    unset($_SESSION['flash_message']);
}

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $location = $_POST['location'];
    $skill = $_POST['skill'];

    // --- IMAGE LOGIC (Optional) ---
    $image_name = NULL; 

    // Updated path to match profile.php location
    $upload_dir = 'assets/images/profilePictures/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0 && !empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            // FAILURE: Stay on register.php
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Invalid file type. Only JPG, JPEG, PNG, WEBP allowed.'];
            header("Location: register.php");
            exit();
        }

        $new_filename = 'labour_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_name = $new_filename;
        } else {
            // FAILURE: Stay on register.php
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Failed to upload image. Please try again.'];
            header("Location: register.php");
            exit();
        }
    }

    // --- DATABASE INSERT ---
    $sql = "INSERT INTO labours (name, email, password, phone, location, skill, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $name, $email, $password, $phone, $location, $skill, $image_name);

    if ($stmt->execute()) {
        // SUCCESS: Redirect to Home Page
        $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Labour registered successfully!'];
        header("Location: home.php"); 
        exit();
    } else {
        // FAILURE: Stay on Register Page (Same Page)
        if ($conn->errno == 1062) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'This email is already registered!'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Database error. Please try again later.'];
        }
        header("Location: register.php"); // Redirects to itself, showing the error
        exit();
    }
    $stmt->close();
}

 $conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Labour</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap"
        rel="stylesheet">
      <link rel="icon" href="assets/images/favicon.png">

    <link href="assets/forms.css" rel="stylesheet">

    <style>
        :root {
            --mustard: #E1AD01;
            --mustard-dark: #C49500;
            --success: #10B981;
            --danger: #EF4444;
            --text-dark: #212529;
        }

        /* --- POPUP STYLES --- */
        .status-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
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

        /* Mustard Accent Line */
        .status-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 6px;
            background: var(--mustard);
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

        .status-card.success .status-icon-circle { background: var(--success); }
        .status-card.error .status-icon-circle { background: var(--danger); }

        .status-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .status-msg {
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 1rem;
            line-height: 1.5;
        }

        .btn-status-close {
            background: var(--text-dark);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-status-close:hover { background: #000; }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-end">
            <div class="col-md-6 bg-opacity-75 p-4">
                <h2 class="text-center mb-4 heading text-dark">Register as Labour</h2>
                <p class="text-center urdu-text text-dark mb-4">
                    Take the first step toward more jobs and better opportunities - register as a labour today<br>
                    زیادہ کام اور بہتر مواقع کے لیے پہلا قدم بڑھائیں — آج ہی لیبر کے طور پر رجسٹر کریں
                </p>

                <!-- Form -->
                <form method="post" action="register.php" enctype="multipart/form-data">

                    <!-- Full Name -->
                    <div class="mb-3">
                        <div class="label-row">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Full Name</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('name')">🔊</button>
                            </div>
                            <span class="urdu-text text-dark small">اپنا نام درج کریں</span>
                        </div>
                        <input type="text" name="name" class="form-control mt-1" placeholder="Enter your full name" required>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <div class="label-row">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Email</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('email')">🔊</button>
                            </div>
                            <span class="urdu-text text-dark small">اپنا ای میل درج کریں</span>
                        </div>
                        <input type="email" name="email" class="form-control mt-1" placeholder="Enter your email" required>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <div class="label-row">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Password</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('password')">🔊</button>
                            </div>
                            <span class="urdu-text text-dark small">اپنا پاس ورڈ درج کریں</span>
                        </div>
                        <input type="password" name="password" class="form-control mt-1" placeholder="Create a password" required>
                    </div>

                    <!-- Profile Image (OPTIONAL) -->
                    <div class="mb-3">
                        <div class="label-row">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Profile Image (Optional)</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('profile')">🔊</button>
                            </div>
                            <span class="urdu-text text-dark small">اپنی پروفائل تصویر شامل کریں</span>
                        </div>
                        <input type="file" name="image" class="form-control mt-1" accept="image/png, image/jpeg, image/jpg, image/webp">
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <div class="label-row">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Phone Number</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('number')">🔊</button>
                            </div>
                            <span class="urdu-text text-dark small">اپنا فون نمبر درج کریں</span>
                        </div>
                        <input type="text" name="phone" class="form-control mt-1" placeholder="Enter your phone number" required>
                    </div>

                    <!-- Location -->
                    <div class="mb-3">
                        <div class="label-row">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Location</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('location')">🔊</button>
                            </div>
                            <span class="urdu-text text-dark small">اپنی لوکیشن شیئر کریں</span>
                        </div>
                        <input type="text" name="location" class="form-control mt-1" placeholder="Enter your city or area" required>
                    </div>

                    <!-- Skill -->
                    <div class="mb-3">
                        <div class="label-row">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Select Your Skill</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('skills')">🔊</button>
                            </div>
                            <span class="urdu-text text-dark small">اپنی مہارت منتخب کریں</span>
                        </div>
                        <select name="skill" class="form-select mt-1" required>
                            <option value="">Choose your profession</option>
                            <option>Plumber</option>
                            <option>Electrician</option>
                            <option>Mechanic</option>
                            <option>Painter</option>
                            <option>Carpenter</option>
                            <option>Mason</option>
                            <option>Labour</option>
                        </select>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" name="submit" class="btn btn-warning btn-lg">Register Now</button>
                    </div>

                    <p class="text-center text-dark mt-3">
                        Already have an account?
                        <a href="login.php" class="text-warning text-decoration-none">Login here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Modern Popup HTML -->
    <div class="status-overlay" id="statusPopup">
        <div class="status-card" id="statusCard">
            <div class="status-icon-circle" id="statusIcon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h3 class="status-title" id="statusTitle">Success!</h3>
            <p class="status-msg" id="statusMessage">Operation completed successfully.</p>
            <button class="btn-status-close" onclick="closePopup()">Okay</button>
        </div>
    </div>

    <script>
        function playBoth(field) {
            const audio1 = new Audio(`audio/${field}-eng.wav`);
            const audio2 = new Audio(`audio/${field}-urdu.wav`);
            audio1.onended = () => audio2.play();
            audio1.play();
        }

        // Handle Popup Logic
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>