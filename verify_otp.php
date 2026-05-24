<?php
session_start();
require_once "connection.php"; 

 $error = "";
 $success = false;

// If no email in session, redirect back to start
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgotpassword.php");
    exit();
}

 $email = $_SESSION['reset_email'];

if (isset($_POST['verify'])) {
    $enteredOtp = $_POST['otp'];
    
    // Find user and check OTP
    $stmt = $conn->prepare("SELECT * FROM labours WHERE email = ? AND reset_token = ?");
    $stmt->bind_param("ss", $email, $enteredOtp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Check expiry
        if (strtotime($user['token_expiry']) > time()) {
            // Valid OTP
            $_SESSION['otp_verified'] = true;
            $_SESSION['reset_user_id'] = $user['labour_id'];
            $_SESSION['reset_table'] = 'labours';
            header("Location: reset_password.php");
            exit();
        } else {
            $error = "⏳ OTP has expired. Please request a new one.";
        }
    } else {
        // Check Customers if not found in Labours
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? AND reset_token = ?");
        $stmt->bind_param("ss", $email, $enteredOtp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (strtotime($user['token_expiry']) > time()) {
                $_SESSION['otp_verified'] = true;
                $_SESSION['reset_user_id'] = $user['customer_id'];
                $_SESSION['reset_table'] = 'customers';
                header("Location: reset_password.php");
                exit();
            } else {
                $error = "⏳ OTP has expired.";
            }
        } else {
            $error = "❌ Invalid OTP. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
            max-width: 400px;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .otp-input {
            letter-spacing: 5px;
            font-size: 1.5rem;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="text-center mb-4 text-warning"><i class="fa-solid fa-shield-halved fa-3x"></i></div>
        <h3 class="text-center mb-2">Verify OTP</h3>
        <p class="text-center text-muted small mb-4">
            Enter the 6-digit code sent to<br>
            <strong><?= htmlspecialchars($email) ?></strong>
        </p>

        <?php if($error): ?>
            <div class="alert alert-danger text-center py-2 small"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="otp" class="form-control otp-input" placeholder="000000" maxlength="6" pattern="\d{6}" required autofocus>
            </div>
            <button type="submit" name="verify" class="btn btn-warning w-100 py-2 fw-bold rounded-pill">Verify Code</button>
        </form>

        <div class="text-center mt-4">
            <a href="forgotpassword.php" class="text-decoration-none text-muted small">Change Email</a>
        </div>
    </div>
</body>
</html>