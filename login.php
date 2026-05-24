<?php
session_start();

// 1. SECURITY: Generate a CSRF Token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for error messages from backend
 $errorCode = isset($_GET['error']) ? $_GET['error'] : '';

// Map error codes to user-friendly messages
 $errorMessage = "";
if ($errorCode == 'invalid_password') {
    $errorMessage = "❌ Incorrect password!";
} elseif ($errorCode == 'user_not_found') {
    $errorMessage = "❌ No account found with this email!";
} elseif ($errorCode == 'csrf_error') {
    $errorMessage = "❌ Security token mismatch. Please try again.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" href="assets/images/favicon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="assets/forms.css" rel="stylesheet">
    
    <!-- Custom CSS for the popup removed -->
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-end">
            <div class="col-md-6 bg-opacity-75 p-4">
                <h2 class="text-center mb-4 heading text-dark">Welcome Back</h2>
                <p class="text-center text-dark mb-4">
                    Your next opportunity starts here - log in to stay visible and get hired faster.
                </p>
                <p class="text-center urdu-text text-dark mb-4">
                    آپ کا اگلا موقع یہیں سے شروع ہوتا ہے - لاگ اِن کریں، متحرک رہیں اور جلدی کام حاصل کریں۔
                </p>

                <?php if ($errorMessage): ?>
                    <!-- 2. SECURITY: htmlspecialchars prevents XSS attacks -->
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST" action="login_auth.php">
                    <!-- 3. SECURITY: CSRF Token Hidden Field -->
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Email</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('email')">🔊</button>
                            </div>
                            <span class="text-dark urdu-text small">اپنا ای میل درج کریں</span>
                        </div>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Password</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('password')">🔊</button>
                            </div>
                            <span class="text-dark urdu-text small">اپنا پاس ورڈ درج کریں</span>
                        </div>
                        <input type="password" name="password" class="form-control" placeholder="Enter Your password" required>
                    </div>

                    <p class="text-start text-dark mt-3">
                       <a href="forgotpassword.php" class="text-danger text-decoration-none">Forgot Password?</a>
                    </p>

                    <div class="d-grid mt-4">
                        <button type="submit" name="submit" class="btn btn-warning btn-lg">Login</button>
                    </div>

                    <p class="text-center text-dark mt-3">
                        Don't have an account? <a href="register.php" class="text-warning text-decoration-none">Create Account</a>
                    </p>
                </form>
            </div>
        </div>
    </div>


    <script>
        function playBoth(field) {
            const audio1 = new Audio(`audio/${field}-eng.wav`);
            const audio2 = new Audio(`audio/${field}-urdu.wav`);
            audio1.onended = () => audio2.play();
            audio1.play();
        }
        <!-- Popup Trigger Script Removed -->
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>