<?php
session_start();
require_once "connection.php"; 

// Security Check: Ensure user came from OTP verification
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: forgotpassword.php");
    exit();
}

 $msg = "";
 $userId = $_SESSION['reset_user_id'];
 $table = $_SESSION['reset_table'];
 $idCol = ($table == 'labours') ? 'labour_id' : 'customer_id';

// Flag to trigger the modal
 $passwordChanged = false;

if (isset($_POST['update_password'])) {
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $msg = "<div class='alert alert-danger'>Passwords do not match!</div>";
    } else {
        // Hash the password
        $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

        // Update Password
        $stmt = $conn->prepare("UPDATE $table SET password = ?, reset_token = NULL, token_expiry = NULL WHERE $idCol = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            // Clear Session
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_table']);
            
            // Set flag to true instead of alerting immediately
            $passwordChanged = true;
        } else {
            $msg = "<div class='alert alert-danger'>Error updating password. Please try again.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #f0ad4e;
            --primary-dark: #ec971f;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            background-image: radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                              radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                              radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-container {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 450px;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .btn-reset {
            background: linear-gradient(45deg, var(--primary), #ffc107);
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(240, 173, 78, 0.4);
        }
        .btn-reset:hover { transform: translateY(-2px); }
        
        /* Custom Modal Styling */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(45deg, var(--primary), #ffc107);
            border-bottom: none;
            color: white;
        }
        .btn-modal-success {
            background: linear-gradient(45deg, var(--primary), #ffc107);
            border: none;
            color: white;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(240, 173, 78, 0.4);
        }
        .btn-modal-success:hover {
            color: white;
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="text-center mb-4 text-warning"><i class="fa-solid fa-lock-open fa-3x"></i></div>
        <h2 class="text-center mb-3">Create New Password</h2>
        <p class="text-center text-muted small mb-4">Please enter your new password below.</p>
        
        <!-- Error Messages -->
        <?= $msg ?>

        <!-- Form (Hidden visually if changed, but kept for structure if modal is closed) -->
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label text-muted small">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" name="update_password" class="btn btn-reset">Update Password</button>
        </form>
    </div>

    <!-- SUCCESS MODAL -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header py-3">
                    <h5 class="modal-title w-100 fw-bold">Success!</h5>
                </div>
                <div class="modal-body p-5">
                    <i class="fa-solid fa-circle-check success-icon"></i>
                    <h4 class="fw-bold mb-3">Password Changed</h4>
                    <p class="text-muted">
                        Your password has been successfully updated. You can now log in with your new password.
                    </p>
                    <button type="button" class="btn btn-modal-success btn-lg mt-3" onclick="window.location.href='login.php'">
                        Go to Login <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Check if PHP flag is set to true
            <?php if ($passwordChanged): ?>
                var myModal = new bootstrap.Modal(document.getElementById('successModal'));
                myModal.show();
            <?php endif; ?>
        });
    </script>
</body>
</html>