<?php
session_start();
require_once "connection.php"; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer/src/Exception.php';
require 'includes/PHPMailer/src/PHPMailer.php';
require 'includes/PHPMailer/src/SMTP.php';

 $message = "";
 $emailSent = false; 

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $userFound = false;
    $tableName = "";
    $userIdCol = "";
    $userId = "";
    $userName = "";

    // Check Labours Table
    $stmt = $conn->prepare("SELECT * FROM labours WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userFound = true;
        $tableName = 'labours';
        $userIdCol = 'labour_id';
        $userId = $user['labour_id'];
        $userName = $user['name'] ?? 'Labour';
    } else {
        // Check Customers Table
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userFound = true;
            $tableName = 'customers';
            $userIdCol = 'customer_id';
            $userId = $user['customer_id'];
            $userName = $user['name'] ?? 'Customer';
        }
    }

    if ($userFound) {
        // Generate OTP
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // Save to DB
        $upd = $conn->prepare("UPDATE $tableName SET reset_token = ?, token_expiry = ? WHERE $userIdCol = ?");
        $upd->bind_param("ssi", $otp, $expiry, $userId);
        
        if ($upd->execute()) {
            // Send Email
            $mail = new PHPMailer(true);

            try {
                // $mail->SMTPDebug = 2; // Uncomment to see errors on screen if needed
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true; 
                
                /* ==================== EDIT THESE 3 LINES ==================== */
                $mail->Username   = 'support.trustedlabour@gmail.com';        
                $mail->Password   = 'ejns sidp xnnr wocl';      
                $mail->setFrom('support.trustedlabour@gmail.com', 'Trusted Labour'); 
                /* ========================================================== */

                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->addAddress($email, $userName);
                $mail->isHTML(true);
                $mail->Subject = 'Your Password Reset OTP';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif;'>
                        <h2 style='color: #f0ad4e;'>Password Reset</h2>
                        <p>Hello $userName,</p>
                        <p>Your code is: <strong>$otp</strong></p>
                        <p>It expires in 15 minutes.</p>
                    </div>
                ";

                $mail->send();
                $_SESSION['reset_email'] = $email;
                $emailSent = true; 

            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>Mailer Error: {$mail->ErrorInfo}</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-warning text-center'>If an account exists, an OTP has been sent.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { --primary: #f0ad4e; --primary-dark: #ec971f; }
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
            border: 1px solid rgba(255,255,255,0.5);
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
        .btn-reset:hover { transform: translateY(-2px); background: linear-gradient(45deg, var(--primary-dark), var(--primary)); }
        
        /* --- UPDATED MODAL STYLING --- */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .modal-body {
            padding: 40px;
            text-align: center;
        }
        .modal-icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, var(--primary), #ffc107);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            box-shadow: 0 4px 15px rgba(240, 173, 78, 0.4);
        }
        .modal-icon-circle i {
            font-size: 2.5rem;
            color: white;
        }
        .btn-modal-action {
            background: linear-gradient(45deg, var(--primary), #ffc107);
            border: none;
            color: white;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            box-shadow: 0 4px 15px rgba(240, 173, 78, 0.4);
            transition: transform 0.2s;
        }
        .btn-modal-action:hover {
            transform: translateY(-2px);
            background: linear-gradient(45deg, var(--primary-dark), var(--primary));
            color: white;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="text-center mb-4 text-warning"><i class="fa-solid fa-envelope-open-text fa-3x"></i></div>
        <h2 class="text-center mb-3">Forgot Password?</h2>
        <p class="text-center text-muted mb-4">Enter your email to receive a verification code.</p>
        
        <?= $message ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label text-muted ms-2">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>
            <button type="submit" name="submit" class="btn btn-reset">Get OTP</button>
        </form>

        <div class="text-center mt-4">
            <a href="login.php" class="text-decoration-none text-muted small"><i class="fa-solid fa-arrow-left me-1"></i> Back to Login</a>
        </div>
    </div>

    <!-- REDESIGNED SUCCESS MODAL -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <!-- Custom Icon Circle with Gradient -->
                    <div class="modal-icon-circle">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    
                    <h3 class="fw-bold mb-3" style="color: #333;">Email Sent!</h3>
                    <p class="text-muted mb-4">
                        We have sent a 6-digit OTP code to your email address. Please check your inbox (and spam folder) to proceed.
                    </p>
                    
                    <!-- Gradient Button matching site theme -->
                    <button type="button" class="btn btn-modal-action btn-lg" onclick="window.location.href='verify_otp.php'">
                        Enter OTP <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if ($emailSent): ?>
                var myModal = new bootstrap.Modal(document.getElementById('successModal'));
                myModal.show();
            <?php endif; ?>
        });
    </script>
</body>
</html>